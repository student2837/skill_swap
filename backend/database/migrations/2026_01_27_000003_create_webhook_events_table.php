<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider'); // paypal, whish, etc
            $table->string('event_type')->nullable();
            $table->string('external_id')->nullable();

            $table->json('headers')->nullable();
            $table->json('payload');

            $table->boolean('processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->text('processing_error')->nullable();

            $table->timestamps();

            $table->index(['provider', 'processed']);
            $table->index(['provider', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};

