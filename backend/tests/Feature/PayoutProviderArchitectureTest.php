<?php

namespace Tests\Feature;

use App\Jobs\ExecutePayoutJob;
use App\Models\Payout;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class PayoutProviderArchitectureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_approval_dispatches_execute_payout_job(): void
    {
        Queue::fake();

        $admin = User::factory()->create(['is_admin' => true, 'credits' => 0]);
        $user = User::factory()->create(['credits' => 100]);

        // Create payout + pending cashout transaction like requestPayout does
        $payout = Payout::create([
            'user_id' => $user->id,
            'amount' => 10,
            'status' => 'pending',
            'provider' => 'manual',
            'idempotency_key' => (string) Str::uuid(),
        ]);
        Transaction::create([
            'user_id' => $user->id,
            'type' => 'cashout',
            'amount' => 10,
            'fee' => 0,
            'status' => 'pending',
            'reference_id' => 'payout_' . $payout->id,
        ]);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/payouts/{$payout->id}/approve")
            ->assertStatus(200);

        Queue::assertPushed(ExecutePayoutJob::class, function (ExecutePayoutJob $job) use ($payout) {
            return $job->payoutId === $payout->id;
        });
    }
}

