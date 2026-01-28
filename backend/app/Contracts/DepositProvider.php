<?php

namespace App\Contracts;

use App\Models\Transaction;
use Illuminate\Http\Request;

interface DepositProvider
{
    /**
     * Create a deposit/collect payment request and return a redirect/collect URL.
     *
     * @return array{collect_url:string, provider_reference:string, raw?:array}
     */
    public function createDeposit(Transaction $transaction, array $context = []): array;

    /**
     * Handle deposit provider webhook/callback.
     *
     * @return array{handled:bool, transaction_id?:int|null}
     */
    public function handleWebhook(Request $request): array;
}

