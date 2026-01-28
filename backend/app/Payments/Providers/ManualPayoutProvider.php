<?php

namespace App\Payments\Providers;

use App\Contracts\PayoutProvider;
use App\Models\Payout;
use Illuminate\Http\Request;
use RuntimeException;

class ManualPayoutProvider implements PayoutProvider
{
    public const PROVIDER = 'manual';

    public function createPayout(Payout $payout): array
    {
        // Manual payouts are executed outside the system (bank transfer/cash/etc).
        // We still return a provider reference for auditability.
        $ref = $payout->provider_reference;
        if (!is_string($ref) || $ref === '') {
            $ref = 'manual_' . $payout->id;
        }

        return ['provider_reference' => $ref, 'raw' => ['note' => 'manual payout']];
    }

    public function getStatus(Payout $payout): array
    {
        // Manual provider cannot fetch status. Status is updated by admin action.
        return ['status' => (string) $payout->status];
    }

    public function handleWebhook(Request $request): array
    {
        // No webhooks for manual provider.
        return ['handled' => true, 'payout_id' => null];
    }
}

