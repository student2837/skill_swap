<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            // Which request this message belongs to
            $table->foreignId('request_id')->constrained('requests')->cascadeOnDelete();

            // Sender and receiver
            $table->foreignId('from_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('to_user_id')->constrained('users')->cascadeOnDelete();

            // Message content
            $table->text('content');

            // Read timestamp
            $table->timestamp('read_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
