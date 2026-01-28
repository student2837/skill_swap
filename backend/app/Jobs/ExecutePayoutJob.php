<?php

namespace App\Jobs;

use App\Services\PayoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExecutePayoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $payoutId)
    {
    }

    public function handle(PayoutService $payoutService): void
    {
        $payoutService->executeApprovedPayout($this->payoutId);
    }
}

