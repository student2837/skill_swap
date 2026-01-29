<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->timestamp('quiz_started_at')->nullable()->after('status');
            $table->timestamp('quiz_completed_at')->nullable()->after('quiz_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn(['quiz_started_at', 'quiz_completed_at']);
        });
    }
};
