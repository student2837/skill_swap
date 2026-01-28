<?php

namespace App\Payments\Providers;

use App\Contracts\DepositProvider;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class WhishDepositProvider implements DepositProvider
{
    public const PROVIDER = 'whish';

    public function createDeposit(Transaction $transaction, array $context = []): array
    {
        // NOTE: Whish "Collect" spec varies by integrator. This implementation is a safe redirect flow
        // that builds a collect URL with a reference, callback, and return URLs.
        // If your Whish integration requires signing, add signature generation here using configured secret.

        $baseUrl = rtrim((string) config('services.whish.collect_base_url'), '/');
        $merchantId = (string) config('services.whish.merchant_id');

        $reference = (string) ($context['reference'] ?? $transaction->reference_id ?? ('whish_' . Str::uuid()));
        $callbackUrl = (string) ($context['callback_url'] ?? config('services.whish.webhook_url'));
        $returnUrl = (string) ($context['return_url'] ?? config('services.whish.return_url'));

        $query = [
            'merchant_id' => $merchantId,
            'reference' => $reference,
            'amount' => (string) ($context['amount'] ?? $transaction->amount), // in credits (or mapped usd); integrator can adjust
            'currency' => (string) ($context['currency'] ?? config('services.whish.currency', 'USD')),
            'callback_url' => $callbackUrl,
            'return_url' => $returnUrl,
            'customer_id' => (string) $transaction->user_id,
        ];

        // Optional: pass-through metadata
        $meta = Arr::get($context, 'meta');
        if (is_array($meta)) {
            $query['meta'] = json_encode($meta);
        }

        $collectUrl = $baseUrl . '/collect?' . http_build_query($query);
        return [
            'collect_url' => $collectUrl,
            'provider_reference' => $reference,
            'raw' => ['query' => $query],
        ];
    }

    public function handleWebhook(Request $request): array
    {
        // Webhook persistence and processing happens in controller/service.
        // Here we only extract the reference.
        $reference = (string) ($request->input('reference') ?? $request->input('ref') ?? '');
        if ($reference === '') {
            return ['handled' => false, 'transaction_id' => null];
        }

        $tx = Transaction::where('reference_id', $reference)->first();
        return ['handled' => $tx !== null, 'transaction_id' => $tx?->id];
    }
}

