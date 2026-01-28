<?php

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhishDepositFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_whish_webhook_confirms_deposit_and_credits_wallet_once(): void
    {
        config()->set('services.whish.webhook_url', 'http://localhost/api/webhooks/whish');
        config()->set('services.whish.return_url', 'http://localhost/return');
        config()->set('services.whish.collect_base_url', 'http://whish.test');

        $user = User::factory()->create(['credits' => 0]);

        $this->actingAs($user, 'sanctum')
            ->postJson('/api/deposits/whish/collect', ['credits' => 10])
            ->assertStatus(200)
            ->assertJsonStructure(['collect_url', 'reference', 'transaction_id']);

        $tx = Transaction::where('user_id', $user->id)->where('type', 'credit_purchase')->firstOrFail();

        // First webhook -> credits added
        $this->postJson('/api/webhooks/whish', [
            'reference' => $tx->reference_id,
            'status' => 'success',
        ])->assertStatus(200);

        $user->refresh();
        $this->assertSame(10, (int) $user->credits);

        // Second webhook (duplicate) -> no double credit
        $this->postJson('/api/webhooks/whish', [
            'reference' => $tx->reference_id,
            'status' => 'success',
        ])->assertStatus(200);

        $user->refresh();
        $this->assertSame(10, (int) $user->credits);
    }
}

