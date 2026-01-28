<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('locked_credits')->default(0)->after('credits');
        });

        Schema::table('payouts', function (Blueprint $table) {
            $table->foreignId('payout_method_id')
                ->nullable()
                ->after('user_id')
                ->constrained('user_payout_methods')
                ->nullOnDelete();
            $table->foreignId('approved_by')
                ->nullable()
                ->after('approved_at')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropForeign(['payout_method_id']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['payout_method_id', 'approved_by']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locked_credits');
        });
    }
};
