<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();

            // Who owns this transaction
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Type of transaction
            $table->enum('type', [
                'credit_purchase',
                'skill_payment',
                'skill_earning',
                'cashout',
                'refund'
            ]);

            // Amount of credits
            $table->integer('amount');

            // Platform fee (if any)
            $table->integer('fee')->default(0);

            // Status
            $table->enum('status', ['pending', 'completed', 'failed']);

            // Optional reference (request_id, payout_id, etc.)
            $table->string('reference_id')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
