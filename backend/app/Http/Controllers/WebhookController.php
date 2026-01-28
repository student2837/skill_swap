<?php

namespace App\Http\Controllers;

use App\Models\Payout;
use App\Models\Transaction;
use App\Models\WebhookEvent;
use App\Payments\Providers\PayPalDepositProvider;
use App\Payments\Providers\PayPalPayoutProvider;
use App\Services\DepositService;
use App\Services\PayoutService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class WebhookController extends Controller
{
    public function paypal(Request $request, PayPalPayoutProvider $paypal, PayoutService $payoutService, DepositService $depositService, PayPalDepositProvider $paypalDeposit): Response
    {
        // Persist webhook first (audit)
        $event = WebhookEvent::create([
            'provider' => PayPalPayoutProvider::PROVIDER,
            'event_type' => (string) $request->input('event_type'),
            'external_id' => (string) ($request->input('id') ?? $request->header('PAYPAL-TRANSMISSION-ID')),
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
            'processed' => false,
        ]);

        try {
            if (!$paypal->verifyWebhookSignature($request)) {
                $event->processing_error = 'invalid_signature';
                $event->save();
                return response('invalid', 400);
            }

            // Idempotency: if already processed, ack
            if ($event->processed) {
                return response('ok', 200);
            }

            $eventType = (string) $request->input('event_type', '');
            $resource = (array) $request->input('resource', []);

            // -------- PayPal DEPOSITS (Orders/Captures) --------
            // We handle deposits here too (same PayPal webhook endpoint & signature verification).
            if (str_starts_with($eventType, 'CHECKOUT.ORDER.') || str_starts_with($eventType, 'PAYMENT.CAPTURE.')) {
                $orderId = '';
                if (str_starts_with($eventType, 'CHECKOUT.ORDER.')) {
                    $orderId = (string) ($resource['id'] ?? '');
                } elseif (str_starts_with($eventType, 'PAYMENT.CAPTURE.')) {
                    $orderId = (string) ($resource['supplementary_data']['related_ids']['order_id'] ?? '');
                }

                if ($orderId !== '') {
                    // On approval, trigger capture server-side; on capture completed, credit wallet.
                    if ($eventType === 'CHECKOUT.ORDER.APPROVED') {
                        $paypalDeposit->captureOrder($orderId);
                    }

                    if ($eventType === 'PAYMENT.CAPTURE.COMPLETED' || $eventType === 'CHECKOUT.ORDER.COMPLETED') {
                        $depositService->confirmPayPalDepositByOrderId($orderId, 'completed', $event);
                    } elseif ($eventType === 'PAYMENT.CAPTURE.DENIED' || $eventType === 'PAYMENT.CAPTURE.FAILED') {
                        $depositService->confirmPayPalDepositByOrderId($orderId, 'failed', $event);
                    } else {
                        // Mark webhook processed even if it doesn't change state
                        $event->processed = true;
                        $event->processed_at = Carbon::now();
                        $event->save();
                    }

                    return response('ok', 200);
                }
            }

            // Map payout by payout_batch_id (we use one payout per batch)
            $batchId = (string) ($resource['payout_batch_id'] ?? $resource['batch_header']['payout_batch_id'] ?? '');
            if ($batchId === '') {
                $event->processed = true;
                $event->processed_at = Carbon::now();
                $event->save();
                return response('ok', 200);
            }

            $payout = Payout::where('provider', PayPalPayoutProvider::PROVIDER)
                ->where('provider_reference', $batchId)
                ->first();

            // If we don't have the mapping yet (race: job stored sender_batch_id but not batch id),
            // try matching on idempotency_key (sender_batch_id) if provider_reference missing.
            if (!$payout) {
                $payout = Payout::where('provider', PayPalPayoutProvider::PROVIDER)
                    ->where('idempotency_key', $batchId)
                    ->first();
            }

            if (!$payout) {
                $event->processed = true;
                $event->processed_at = Carbon::now();
                $event->save();
                return response('ok', 200);
            }

            // Update provider_reference if missing
            if (empty($payout->provider_reference)) {
                $payout->provider_reference = $batchId;
                $payout->save();
            }

            // PayPal payout events
            if (str_starts_with($eventType, 'PAYMENT.PAYOUTS-ITEM') || str_starts_with($eventType, 'PAYOUTS-ITEM')) {
                $txStatus = strtoupper((string) ($resource['transaction_status'] ?? $resource['transaction_status'] ?? ''));
                if (in_array($txStatus, ['SUCCESS', 'SUCCESSFUL', 'COMPLETED'], true)) {
                    $payoutService->markPaid($payout);
                } elseif (in_array($txStatus, ['FAILED', 'DENIED', 'CANCELED', 'CANCELLED'], true)) {
                    $payoutService->markFailed($payout, $txStatus, 'PayPal payout item ' . strtolower($txStatus));
                }
            } else {
                // Fallback: reconcile from provider
                $provStatus = $paypal->getStatus($payout);
                if (($provStatus['status'] ?? null) === 'paid') {
                    $payoutService->markPaid($payout);
                } elseif (($provStatus['status'] ?? null) === 'failed') {
                    $payoutService->markFailed($payout, $provStatus['failure_code'] ?? null, $provStatus['failure_message'] ?? null);
                }
            }

            $event->processed = true;
            $event->processed_at = Carbon::now();
            $event->save();

            return response('ok', 200);
        } catch (\Throwable $e) {
            Log::error('PayPal webhook processing failed', ['error' => $e->getMessage()]);
            $event->processing_error = $e->getMessage();
            $event->save();
            return response('error', 500);
        }
    }

    public function whish(Request $request, DepositService $depositService): Response
    {
        // Persist webhook/callback first (audit)
        $event = WebhookEvent::create([
            'provider' => 'whish',
            'event_type' => (string) ($request->input('event') ?? $request->input('status')),
            'external_id' => (string) ($request->input('id') ?? $request->input('transaction_id') ?? ''),
            'headers' => $request->headers->all(),
            'payload' => $request->all(),
            'processed' => false,
        ]);

        try {
            $reference = (string) ($request->input('reference') ?? $request->input('ref') ?? '');
            $status = (string) ($request->input('status') ?? $request->input('result') ?? '');

            if ($reference === '' || $status === '') {
                $event->processed = true;
                $event->processed_at = Carbon::now();
                $event->processing_error = 'missing_reference_or_status';
                $event->save();
                return response('ok', 200);
            }

            $depositService->confirmWhishDeposit($reference, $status, $event);
            return response('ok', 200);
        } catch (\Throwable $e) {
            Log::error('Whish webhook processing failed', ['error' => $e->getMessage()]);
            $event->processing_error = $e->getMessage();
            $event->save();
            return response('error', 500);
        }
    }
}

