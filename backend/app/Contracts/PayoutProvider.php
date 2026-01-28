<?php

namespace App\Contracts;

use App\Models\Payout;
use Illuminate\Http\Request;

interface PayoutProvider
{
    /**
     * Create a payout at the provider.
     *
     * Must be idempotent with respect to $payout->idempotency_key.
     *
     * @return array{provider_reference:string, raw?:array}
     */
    public function createPayout(Payout $payout): array;

    /**
     * Fetch status from provider using provider_reference.
     *
     * @return array{status:string, failure_code?:string|null, failure_message?:string|null, raw?:array}
     */
    public function getStatus(Payout $payout): array;

    /**
     * Handle an incoming webhook. Implementations should NOT write WebhookEvent rows;
     * the controller persists webhooks for audit first, then calls this.
     *
     * @return array{handled:bool, payout_id?:int|null}
     */
    public function handleWebhook(Request $request): array;
}

