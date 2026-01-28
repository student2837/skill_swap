<?php

namespace App\Payments\Providers;

use App\Contracts\PayoutProvider;
use App\Models\Payout;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PayPalPayoutProvider implements PayoutProvider
{
    public const PROVIDER = 'paypal';

    protected function http(): PendingRequest
    {
        return Http::timeout((int) config('services.paypal.timeout', 20))
            ->acceptJson();
    }

    protected function baseUrl(): string
    {
        return rtrim((string) config('services.paypal.base_url'), '/');
    }

    protected function accessToken(): string
    {
        $cacheKey = 'paypal:access_token:' . sha1((string) config('services.paypal.client_id'));

        /** @var array{access_token:string, expires_in:int}|null $cached */
        $cached = Cache::get($cacheKey);
        if (is_array($cached) && !empty($cached['access_token'])) {
            return $cached['access_token'];
        }

        $clientId = (string) config('services.paypal.client_id');
        $secret = (string) config('services.paypal.secret');
        if ($clientId === '' || $secret === '') {
            throw new RuntimeException('PayPal client credentials not configured');
        }

        $resp = $this->http()
            ->withBasicAuth($clientId, $secret)
            ->asForm()
            ->post($this->baseUrl() . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (!$resp->successful()) {
            throw new RuntimeException('PayPal OAuth failed: ' . $resp->body());
        }

        $data = $resp->json();
        $token = (string) ($data['access_token'] ?? '');
        $expiresIn = (int) ($data['expires_in'] ?? 0);
        if ($token === '' || $expiresIn <= 0) {
            throw new RuntimeException('PayPal OAuth response missing token');
        }

        // cache a bit less than real expiry
        Cache::put($cacheKey, ['access_token' => $token, 'expires_in' => $expiresIn], max(60, $expiresIn - 60));
        return $token;
    }

    public function createPayout(Payout $payout): array
    {
        $payout->loadMissing(['user']);

        $receiver = $payout->method_details['receiver'] ?? null;
        if (!is_string($receiver) || $receiver === '') {
            throw new RuntimeException('PayPal payout receiver not configured for payout');
        }

        // sender_batch_id is PayPal idempotency key for Payouts API
        $senderBatchId = $payout->idempotency_key ?: (string) Str::uuid();

        $body = [
            'sender_batch_header' => [
                'sender_batch_id' => $senderBatchId,
                'email_subject' => config('services.paypal.payout_email_subject', 'You have a payout'),
            ],
            'items' => [
                [
                    'recipient_type' => 'EMAIL',
                    'amount' => [
                        'value' => number_format($payout->amount_usd, 2, '.', ''),
                        'currency' => (string) config('services.paypal.currency', 'USD'),
                    ],
                    'receiver' => $receiver,
                    'note' => config('services.paypal.payout_note', 'SkillSwap cashout'),
                    'sender_item_id' => 'payout_' . $payout->id,
                ],
            ],
        ];

        $resp = $this->http()
            ->withToken($this->accessToken())
            ->post($this->baseUrl() . '/v1/payments/payouts', $body);

        // If a duplicate sender_batch_id occurs, PayPal may return 201 with same batch id,
        // or 400 with a DUPLICATE_SENDER_BATCH_ID error; we treat that as idempotent success
        if (!$resp->successful()) {
            $json = $resp->json();
            $name = $json['name'] ?? null;
            if ($resp->status() === 400 && $name === 'DUPLICATE_SENDER_BATCH_ID') {
                // We can still reconcile by sender_batch_id (idempotency_key)
                return ['provider_reference' => $senderBatchId, 'raw' => $json ?? []];
            }
            throw new RuntimeException('PayPal create payout failed: ' . $resp->body());
        }

        $data = $resp->json();
        $batchId = (string) ($data['batch_header']['payout_batch_id'] ?? '');
        if ($batchId === '') {
            // fallback: at least store sender_batch_id so reconciliation can locate it
            $batchId = $senderBatchId;
        }

        return ['provider_reference' => $batchId, 'raw' => $data ?? []];
    }

    public function getStatus(Payout $payout): array
    {
        $ref = (string) $payout->provider_reference;
        if ($ref === '') {
            return ['status' => 'unknown'];
        }

        $resp = $this->http()
            ->withToken($this->accessToken())
            ->get($this->baseUrl() . '/v1/payments/payouts/' . urlencode($ref));

        if (!$resp->successful()) {
            return ['status' => 'unknown', 'raw' => $resp->json() ?? []];
        }

        $data = $resp->json();
        $batchStatus = strtoupper((string) ($data['batch_header']['batch_status'] ?? ''));

        // Map PayPal batch statuses to our statuses
        return match ($batchStatus) {
            'SUCCESS' => ['status' => 'paid', 'raw' => $data ?? []],
            'DENIED', 'CANCELED', 'FAILED' => [
                'status' => 'failed',
                'failure_code' => $batchStatus,
                'failure_message' => 'PayPal payout batch ' . strtolower($batchStatus),
                'raw' => $data ?? [],
            ],
            default => ['status' => 'processing', 'raw' => $data ?? []],
        };
    }

    public function handleWebhook(Request $request): array
    {
        // Webhook verification and persistence is handled by controller.
        // Here we only parse enough info for downstream reconciliation.
        $eventType = (string) $request->input('event_type', '');
        $resource = (array) $request->input('resource', []);

        // For PAYOUTS-ITEM-* events, resource includes payout_batch_id
        $batchId = (string) ($resource['payout_batch_id'] ?? '');
        if ($batchId === '') {
            return ['handled' => false, 'payout_id' => null];
        }

        $payout = Payout::where('provider', self::PROVIDER)
            ->where('provider_reference', $batchId)
            ->first();

        return ['handled' => $payout !== null, 'payout_id' => $payout?->id];
    }

    /**
     * Verify PayPal webhook signature by calling PayPal verify endpoint.
     */
    public function verifyWebhookSignature(Request $request): bool
    {
        $webhookId = (string) config('services.paypal.webhook_id');
        if ($webhookId === '') {
            throw new RuntimeException('PayPal webhook_id not configured');
        }

        $payload = [
            'auth_algo' => (string) $request->header('PAYPAL-AUTH-ALGO'),
            'cert_url' => (string) $request->header('PAYPAL-CERT-URL'),
            'transmission_id' => (string) $request->header('PAYPAL-TRANSMISSION-ID'),
            'transmission_sig' => (string) $request->header('PAYPAL-TRANSMISSION-SIG'),
            'transmission_time' => (string) $request->header('PAYPAL-TRANSMISSION-TIME'),
            'webhook_id' => $webhookId,
            'webhook_event' => $request->all(),
        ];

        $resp = $this->http()
            ->withToken($this->accessToken())
            ->post($this->baseUrl() . '/v1/notifications/verify-webhook-signature', $payload);

        if (!$resp->successful()) {
            return false;
        }

        $verification = (string) ($resp->json('verification_status') ?? '');
        return strtoupper($verification) === 'SUCCESS';
    }
}

