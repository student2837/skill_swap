<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->integer('gross_amount')->nullable()->after('amount');
            $table->integer('fee_amount')->default(0)->after('gross_amount');
            $table->integer('net_amount')->nullable()->after('fee_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropColumn(['gross_amount', 'fee_amount', 'net_amount']);
        });
    }
};

