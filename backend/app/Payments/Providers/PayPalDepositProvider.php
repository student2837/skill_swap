<?php

namespace App\Payments\Providers;

use App\Contracts\DepositProvider;
use App\Models\Transaction;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class PayPalDepositProvider implements DepositProvider
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

        $cached = Cache::get($cacheKey);
        if (is_array($cached) && !empty($cached['access_token'])) {
            return (string) $cached['access_token'];
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

        Cache::put($cacheKey, ['access_token' => $token, 'expires_in' => $expiresIn], max(60, $expiresIn - 60));
        return $token;
    }

    /**
     * Create a PayPal Checkout order and return approval_url.
     */
    public function createDeposit(Transaction $transaction, array $context = []): array
    {
        $currency = (string) config('services.paypal.currency', 'USD');

        $returnUrl = (string) ($context['return_url'] ?? config('services.paypal.checkout_return_url'));
        $cancelUrl = (string) ($context['cancel_url'] ?? config('services.paypal.checkout_cancel_url'));
        if ($returnUrl === '' || $cancelUrl === '') {
            $appUrl = rtrim((string) config('app.url'), '/');
            $returnUrl = $returnUrl ?: ($appUrl . '/credits/status');
            $cancelUrl = $cancelUrl ?: ($appUrl . '/credits');
        }

        // 1 credit = 1 USD
        $usd = number_format((float) $transaction->amount, 2, '.', '');
        $invoiceId = $transaction->reference_id ?: ('pp_inv_' . (string) Str::uuid());

        $body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [
                [
                    'reference_id' => 'credits',
                    'custom_id' => (string) $transaction->id, // use to map back idempotently
                    'invoice_id' => $invoiceId,
                    'amount' => [
                        'currency_code' => $currency,
                        'value' => $usd,
                    ],
                    'description' => 'SkillSwap credits',
                ],
            ],
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
                'brand_name' => 'SkillSwap',
                'user_action' => 'PAY_NOW',
            ],
        ];

        $resp = $this->http()
            ->withToken($this->accessToken())
            ->post($this->baseUrl() . '/v2/checkout/orders', $body);

        if (!$resp->successful()) {
            throw new RuntimeException('PayPal create order failed: ' . $resp->body());
        }

        $data = $resp->json();
        $orderId = (string) ($data['id'] ?? '');
        if ($orderId === '') {
            throw new RuntimeException('PayPal order response missing id');
        }

        $approvalUrl = '';
        foreach (($data['links'] ?? []) as $link) {
            if (($link['rel'] ?? '') === 'approve') {
                $approvalUrl = (string) ($link['href'] ?? '');
                break;
            }
        }
        if ($approvalUrl === '') {
            throw new RuntimeException('PayPal order response missing approval url');
        }

        return [
            'collect_url' => $approvalUrl,
            'provider_reference' => $orderId,
            'raw' => $data ?? [],
        ];
    }

    /**
     * Capture a PayPal order (idempotent on PayPal side; repeated calls return same result or error).
     */
    public function captureOrder(string $orderId): array
    {
        $resp = $this->http()
            ->withToken($this->accessToken())
            ->post($this->baseUrl() . '/v2/checkout/orders/' . urlencode($orderId) . '/capture');

        if (!$resp->successful()) {
            return ['ok' => false, 'raw' => $resp->json() ?? ['body' => $resp->body()]];
        }
        return ['ok' => true, 'raw' => $resp->json() ?? []];
    }

    public function handleWebhook(Request $request): array
    {
        // webhook persistence + signature verification handled in controller
        $eventType = (string) $request->input('event_type', '');
        $resource = (array) $request->input('resource', []);

        $orderId = '';
        if (str_starts_with($eventType, 'CHECKOUT.ORDER')) {
            $orderId = (string) ($resource['id'] ?? '');
        } elseif (str_starts_with($eventType, 'PAYMENT.CAPTURE')) {
            $orderId = (string) ($resource['supplementary_data']['related_ids']['order_id'] ?? '');
        }

        return ['handled' => $orderId !== '', 'transaction_id' => null];
    }
}

