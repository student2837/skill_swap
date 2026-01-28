<?php

namespace App\Services;

use App\Contracts\PayoutProvider;
use App\Models\Payout;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserPayoutMethod;
use App\Payments\Providers\ManualPayoutProvider;
use App\Payments\Providers\PayPalPayoutProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class PayoutService
{
    public function __construct(
        protected WalletService $walletService,
        protected PayPalPayoutProvider $paypalProvider,
        protected ManualPayoutProvider $manualProvider,
    ) {}

    public function providerFor(Payout $payout): PayoutProvider
    {
        return match ($payout->provider) {
            PayPalPayoutProvider::PROVIDER => $this->paypalProvider,
            default => $this->manualProvider,
        };
    }

    /**
     * Ensure payout has method_details for chosen provider.
     * For paypal: expects ['receiver' => '<email>'].
     */
    public function hydrateMethodDetails(Payout $payout): void
    {
        if ($payout->provider !== PayPalPayoutProvider::PROVIDER) {
            return;
        }

        if (is_array($payout->method_details) && !empty($payout->method_details['receiver'])) {
            return;
        }

        $methodQuery = UserPayoutMethod::where('user_id', $payout->user_id)
            ->where('provider', PayPalPayoutProvider::PROVIDER);
        if ($payout->payout_method_id) {
            $methodQuery->where('id', $payout->payout_method_id);
        } else {
            $methodQuery->where('is_default', true);
        }
        $method = $methodQuery->first();

        if (!$method) {
            throw new RuntimeException('No default PayPal payout method configured for user');
        }

        $details = $method->details;
        $receiver = $details['provider_reference'] ?? $details['receiver'] ?? $details['email'] ?? null;
        if (!is_string($receiver) || $receiver === '') {
            throw new RuntimeException('Invalid PayPal payout method details');
        }

        $payout->method = $payout->method ?: ($method->method ?: 'paypal_email');
        $payout->method_details = ['receiver' => $receiver];
        $payout->save();
    }

    public function requestPayout(User $user, int $gross, UserPayoutMethod $method): Payout
    {
        $min = (int) config('payments.cashout_min', 10);
        if ($gross < $min) {
            throw new RuntimeException('Minimum cashout amount is ' . $min . ' credits');
        }

        $feeRate = $user->is_admin ? 0.0 : (float) config('payments.cashout_fee_rate', 0.20);
        $fee = (int) floor($gross * $feeRate);
        $net = max(0, $gross - $fee);
        if ($net <= 0) {
            throw new RuntimeException('Invalid payout amount after fee');
        }

        return DB::transaction(function () use ($user, $gross, $fee, $net, $method) {
            $this->walletService->lockCredits($user->id, $gross);

            $details = $method->getSafeDetails();
            $methodDetails = [
                'label' => $details['label'] ?? null,
                'last4' => $details['last4'] ?? null,
                'provider_reference' => $details['provider_reference'] ?? null,
            ];

            if ($method->provider === PayPalPayoutProvider::PROVIDER && $details['provider_reference'] ?? null) {
                $methodDetails['receiver'] = $details['provider_reference'];
            }

            $payload = [
                'user_id' => $user->id,
                'amount' => $net,
                'gross_amount' => $gross,
                'fee_amount' => $fee,
                'net_amount' => $net,
                'status' => 'pending',
                'provider' => $method->provider,
                'method' => $method->method,
                'method_details' => $methodDetails,
                'idempotency_key' => (string) Str::uuid(),
            ];
            if (Schema::hasColumn('payouts', 'payout_method_id')) {
                $payload['payout_method_id'] = $method->id;
            }

            $payout = Payout::create($payload);

            Transaction::create([
                'user_id' => $user->id,
                'type' => 'cashout',
                'amount' => $gross,
                'fee' => $fee,
                'status' => 'pending',
                'reference_id' => 'payout_' . $payout->id,
            ]);

            return $payout;
        });
    }

    public function markApprovedAndDispatch(Payout $payout, int $adminUserId): void
    {
        DB::transaction(function () use ($payout, $adminUserId) {
            /** @var Payout $locked */
            $locked = Payout::whereKey($payout->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') {
                throw new RuntimeException('Invalid payout status for approval');
            }
            $gross = (int) ($locked->gross_amount ?? $locked->amount ?? 0);
            if ($gross <= 0) {
                throw new RuntimeException('Invalid payout amount');
            }

            $locked->approved_at = Carbon::now();
            if (Schema::hasColumn('payouts', 'approved_by')) {
                $locked->approved_by = $adminUserId;
            }
            if (empty($locked->idempotency_key)) {
                $locked->idempotency_key = (string) Str::uuid();
            }

            // Deduct locked credits atomically on approval (fallback to legacy debit if needed)
            try {
                $this->walletService->consumeLockedCredits($locked->user_id, $gross);
            } catch (RuntimeException $e) {
                if ($e->getMessage() === 'Locked credits insufficient') {
                    $this->walletService->debitCredits($locked->user_id, $gross);
                } else {
                    throw $e;
                }
            }

            // For automated providers (PayPal, etc.): just approve, job will handle processing
            $locked->status = 'approved';
            $locked->save();

            // Credit platform fee to the approving admin (0% for admin cashouts)
            $fee = (int) ($locked->fee_amount ?? 0);
            if ($fee > 0) {
                /** @var User $admin */
                $admin = User::whereKey($adminUserId)->lockForUpdate()->firstOrFail();
                $admin->credits = (int) ($admin->credits ?? 0) + $fee;
                $admin->save();

                // Record fee as an "earning" for platform wallet UI
                Transaction::create([
                    'user_id' => $admin->id,
                    'type' => 'skill_earning',
                    'amount' => $fee,
                    'fee' => 0,
                    'status' => 'completed',
                    'reference_id' => 'payout_fee_' . $locked->id,
                ]);
            }
        });
    }

    public function markRejected(Payout $payout, string $adminNote): void
    {
        DB::transaction(function () use ($payout, $adminNote) {
            /** @var Payout $locked */
            $locked = Payout::whereKey($payout->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'rejected') {
                return;
            }

            $locked->status = 'rejected';
            $locked->admin_note = $adminNote;
            $locked->processed_at = Carbon::now();
            $locked->save();

            // Unlock credits for rejected payouts (credits were locked at request time)
            $refund = (int) ($locked->gross_amount ?? $locked->amount);
            if ($refund > 0) {
                try {
                    $this->walletService->unlockCredits($locked->user_id, $refund);
                } catch (RuntimeException $e) {
                    if ($e->getMessage() === 'Locked credits insufficient') {
                        $this->walletService->creditCredits($locked->user_id, $refund);
                    } else {
                        throw $e;
                    }
                }
            }

            Transaction::where('reference_id', 'payout_' . $locked->id)
                ->where('type', 'cashout')
                ->where('status', 'pending')
                ->update(['status' => 'failed']);
        });
    }

    public function markPaid(Payout $payout): void
    {
        DB::transaction(function () use ($payout) {
            /** @var Payout $locked */
            $locked = Payout::whereKey($payout->id)->lockForUpdate()->firstOrFail();
            $locked->status = 'paid';
            $locked->processed_at = Carbon::now();
            $locked->failure_code = null;
            $locked->failure_message = null;
            $locked->save();

            Transaction::where('reference_id', 'payout_' . $locked->id)
                ->where('type', 'cashout')
                ->where('status', 'pending')
                ->update(['status' => 'completed']);
        });
    }

    public function markFailed(Payout $payout, ?string $code = null, ?string $message = null): void
    {
        DB::transaction(function () use ($payout, $code, $message) {
            /** @var Payout $locked */
            $locked = Payout::whereKey($payout->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === 'paid') {
                return;
            }
            $locked->status = 'failed';
            $locked->processed_at = Carbon::now();
            $locked->failure_code = $code;
            $locked->failure_message = $message;
            $locked->save();

            // Unlock credits on failure (credits were locked at request time)
            $refund = (int) ($locked->gross_amount ?? $locked->amount);
            if ($refund > 0) {
                try {
                    $this->walletService->unlockCredits($locked->user_id, $refund);
                } catch (RuntimeException $e) {
                    if ($e->getMessage() === 'Locked credits insufficient') {
                        $this->walletService->creditCredits($locked->user_id, $refund);
                    } else {
                        throw $e;
                    }
                }
            }

            Transaction::where('reference_id', 'payout_' . $locked->id)
                ->where('type', 'cashout')
                ->where('status', 'pending')
                ->update(['status' => 'failed']);
        });
    }

    /**
     * Called by job: performs an idempotent transition approved->processing and initiates provider payout.
     */
    public function executeApprovedPayout(int $payoutId): void
    {
        $payout = null;

        DB::transaction(function () use ($payoutId, &$payout) {
            /** @var Payout $locked */
            $locked = Payout::whereKey($payoutId)->lockForUpdate()->firstOrFail();

            // Idempotency: if already processing/paid/failed, do nothing.
            if (in_array($locked->status, ['processing', 'paid', 'failed'], true)) {
                $payout = $locked;
                return;
            }

            if ($locked->status !== 'approved') {
                throw new RuntimeException('Payout not approved');
            }

            // Manual payouts should not be executed by provider.
            if ($locked->provider === ManualPayoutProvider::PROVIDER) {
                throw new RuntimeException('Manual payouts require admin completion');
            }

            if (empty($locked->idempotency_key)) {
                $locked->idempotency_key = (string) Str::uuid();
            }

            // For automated providers (PayPal, etc.), set to processing
            $locked->status = 'processing';
            $locked->save();
            $payout = $locked;
        });

        if (!$payout instanceof Payout) {
            return;
        }

        // Fill method_details if needed
        $this->hydrateMethodDetails($payout);

        $provider = $this->providerFor($payout);

        try {
            $result = $provider->createPayout($payout);

            DB::transaction(function () use ($payout, $result) {
                /** @var Payout $locked */
                $locked = Payout::whereKey($payout->id)->lockForUpdate()->firstOrFail();

                // If another worker already set provider_reference, keep it.
                if (empty($locked->provider_reference) && !empty($result['provider_reference'])) {
                    $locked->provider_reference = (string) $result['provider_reference'];
                }
                $locked->save();
            });
        } catch (\Throwable $e) {
            Log::error('Execute payout failed', ['payout_id' => $payout->id, 'error' => $e->getMessage()]);
            $this->markFailed($payout, 'provider_error', $e->getMessage());
            throw $e;
        }
    }
}

