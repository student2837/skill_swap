<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            // Add conversation_id column
            $table->foreignId('conversation_id')->nullable()->after('id')->constrained('conversations')->cascadeOnDelete();
            
            // Make request_id nullable (we'll migrate existing data first)
            $table->foreignId('request_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
            $table->dropColumn('conversation_id');
            $table->foreignId('request_id')->nullable(false)->change();
        });
    }
};
