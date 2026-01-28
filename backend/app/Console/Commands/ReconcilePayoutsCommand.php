<?php

namespace App\Console\Commands;

use App\Models\Payout;
use App\Payments\Providers\PayPalPayoutProvider;
use App\Services\PayoutService;
use Illuminate\Console\Command;

class ReconcilePayoutsCommand extends Command
{
    protected $signature = 'payouts:reconcile {--provider=paypal} {--limit=50}';
    protected $description = 'Reconcile processing payouts against provider status';

    public function handle(PayoutService $payoutService, PayPalPayoutProvider $paypal): int
    {
        $provider = (string) $this->option('provider');
        $limit = (int) $this->option('limit');

        $query = Payout::query()
            ->where('status', 'processing')
            ->whereNotNull('provider_reference');

        if ($provider !== '') {
            $query->where('provider', $provider);
        }

        $payouts = $query->limit($limit)->get();

        foreach ($payouts as $payout) {
            $prov = $payoutService->providerFor($payout);
            $status = $prov->getStatus($payout);

            if (($status['status'] ?? null) === 'paid') {
                $payoutService->markPaid($payout);
                $this->info("Payout {$payout->id} marked paid");
            } elseif (($status['status'] ?? null) === 'failed') {
                $payoutService->markFailed($payout, $status['failure_code'] ?? null, $status['failure_message'] ?? null);
                $this->warn("Payout {$payout->id} marked failed");
            } else {
                $this->line("Payout {$payout->id} still processing");
            }
        }

        return self::SUCCESS;
    }
}

