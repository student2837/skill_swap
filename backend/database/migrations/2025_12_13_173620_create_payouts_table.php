<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payouts', function (Blueprint $table) {
            $table->id();

            // User requesting payout
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Credits amount
            $table->integer('amount');

            // Status of payout
            $table->enum('status', ['pending', 'approved', 'rejected', 'paid'])
                ->default('pending');

            // Admin notes (optional)
            $table->text('admin_note')->nullable();

            // When payout was processed
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payouts');
    }
};
