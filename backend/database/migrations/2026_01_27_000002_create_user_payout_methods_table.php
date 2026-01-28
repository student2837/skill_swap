<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_payout_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('provider'); // e.g. paypal
            $table->string('method')->nullable(); // e.g. paypal_email

            // Encrypted JSON blob (string ciphertext). Use app-level encryption.
            $table->text('details_encrypted');

            $table->boolean('is_default')->default(false);
            $table->boolean('is_verified')->default(false);

            $table->timestamps();

            $table->index(['user_id', 'provider']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_payout_methods');
    }
};

