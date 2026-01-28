<?php

namespace App\Services;

use App\Contracts\DepositProvider;
use App\Models\Transaction;
use App\Models\WebhookEvent;
use App\Payments\Providers\PayPalDepositProvider;
use App\Payments\Providers\WhishDepositProvider;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class DepositService
{
    public function __construct(
        protected WalletService $walletService,
        protected PayPalDepositProvider $paypalProvider,
        protected WhishDepositProvider $whishProvider,
    ) {}

    public function providerFor(string $provider): DepositProvider
    {
        return match ($provider) {
            PayPalDepositProvider::PROVIDER => $this->paypalProvider,
            WhishDepositProvider::PROVIDER => $this->whishProvider,
            default => throw new RuntimeException('Unsupported deposit provider'),
        };
    }

    /**
     * Create a PayPal order for buying credits. Returns approval_url.
     * Credits are credited ONLY after webhook-confirmed capture.
     */
    public function createPayPalOrder(int $userId, int $credits, array $context = []): array
    {
        if ($credits <= 0) {
            throw new RuntimeException('Credits must be positive');
        }

        // temporary reference until we have PayPal order id
        $tmpRef = 'paypal_tmp_' . (string) Str::uuid();

        $tx = DB::transaction(function () use ($userId, $credits, $tmpRef) {
            return Transaction::create([
                'user_id' => $userId,
                'type' => 'credit_purchase',
                'amount' => $credits,
                'fee' => 0,
                'status' => 'pending',
                'reference_id' => $tmpRef,
            ]);
        });

        $result = $this->paypalProvider->createDeposit($tx, $context);
        $orderId = (string) ($result['provider_reference'] ?? '');
        if ($orderId === '') {
            throw new RuntimeException('PayPal order id missing');
        }

        // Store PayPal order id in reference_id for status tracking
        $reference = 'paypal_order_' . $orderId;
        $tx->reference_id = $reference;
        $tx->save();

        return [
            'transaction_id' => $tx->id,
            'reference' => $reference,
            'approval_url' => $result['collect_url'], // approval_url for PayPal
            'order_id' => $orderId,
        ];
    }

    /**
     * Confirm PayPal capture via webhook and credit wallet idempotently.
     */
    public function confirmPayPalDepositByOrderId(string $orderId, string $status, ?WebhookEvent $event = null): ?Transaction
    {
        $normalized = strtolower(trim($status));
        $isSuccess = in_array($normalized, ['completed', 'success'], true);
        $isFailed = in_array($normalized, ['failed', 'denied', 'canceled', 'cancelled', 'declined'], true);

        $reference = 'paypal_order_' . $orderId;

        return DB::transaction(function () use ($reference, $isSuccess, $isFailed, $event) {
            /** @var Transaction|null $tx */
            $tx = Transaction::where('reference_id', $reference)->lockForUpdate()->first();
            if (!$tx) {
                return null;
            }

            if ($tx->status === 'completed') {
                if ($event) {
                    $event->processed = true;
                    $event->processed_at = Carbon::now();
                    $event->save();
                }
                return $tx;
            }

            if ($isFailed) {
                $tx->status = 'failed';
                $tx->save();
                if ($event) {
                    $event->processed = true;
                    $event->processed_at = Carbon::now();
                    $event->save();
                }
                return $tx;
            }

            if ($isSuccess) {
                $this->walletService->creditCredits($tx->user_id, (int) $tx->amount);
                $tx->status = 'completed';
                $tx->save();
                if ($event) {
                    $event->processed = true;
                    $event->processed_at = Carbon::now();
                    $event->save();
                }
                return $tx;
            }

            return $tx;
        });
    }

    /**
     * Create a pending credit_purchase transaction and return provider collect URL.
     *
     * Transaction.amount is credits-to-grant (NOT USD) so wallet crediting remains internal.
     */
    public function createWhishCollect(int $userId, int $credits, array $context = []): array
    {
        if ($credits <= 0) {
            throw new RuntimeException('Credits must be positive');
        }

        $reference = 'whish_collect_' . (string) Str::uuid();

        $tx = DB::transaction(function () use ($userId, $credits, $reference) {
            return Transaction::create([
                'user_id' => $userId,
                'type' => 'credit_purchase',
                'amount' => $credits,
                'fee' => 0,
                'status' => 'pending',
                'reference_id' => $reference,
            ]);
        });

        $result = $this->whishProvider->createDeposit($tx, array_merge($context, [
            'reference' => $reference,
        ]));

        return [
            'transaction_id' => $tx->id,
            'reference' => $reference,
            'collect_url' => $result['collect_url'],
            'provider_reference' => $result['provider_reference'],
        ];
    }

    /**
     * Confirm a Whish payment and credit the wallet once.
     * This is designed to be called from webhook/callback handler.
     */
    public function confirmWhishDeposit(string $reference, string $status, ?WebhookEvent $event = null): ?Transaction
    {
        $normalized = strtolower(trim($status));
        $isSuccess = in_array($normalized, ['success', 'completed', 'paid', 'approved'], true);
        $isFailed = in_array($normalized, ['failed', 'cancelled', 'canceled', 'error', 'declined'], true);

        return DB::transaction(function () use ($reference, $isSuccess, $isFailed, $event) {
            /** @var Transaction|null $tx */
            $tx = Transaction::where('reference_id', $reference)->lockForUpdate()->first();
            if (!$tx) {
                return null;
            }

            // Idempotency: only credit once
            if ($tx->status === 'completed') {
                if ($event) {
                    $event->processed = true;
                    $event->processed_at = Carbon::now();
                    $event->save();
                }
                return $tx;
            }

            if ($isFailed) {
                $tx->status = 'failed';
                $tx->save();
                if ($event) {
                    $event->processed = true;
                    $event->processed_at = Carbon::now();
                    $event->save();
                }
                return $tx;
            }

            if ($isSuccess) {
                // credit wallet AFTER confirmed
                $this->walletService->creditCredits($tx->user_id, (int) $tx->amount);
                $tx->status = 'completed';
                $tx->save();

                if ($event) {
                    $event->processed = true;
                    $event->processed_at = Carbon::now();
                    $event->save();
                }
                return $tx;
            }

            // Unknown status: leave pending for reconciliation.
            return $tx;
        });
    }
}

