<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payouts', function (Blueprint $table) {
            $table->string('provider')->default('manual')->after('amount');
            $table->string('method')->nullable()->after('provider');
            $table->string('provider_reference')->nullable()->after('method');
            $table->uuid('idempotency_key')->nullable()->after('provider_reference');
            $table->timestamp('approved_at')->nullable()->after('idempotency_key');
            $table->timestamp('processed_at')->nullable()->change();
            $table->string('failure_code')->nullable()->after('processed_at');
            $table->text('failure_message')->nullable()->after('failure_code');
            $table->json('method_details')->nullable()->after('failure_message');
        });

        // Ensure idempotency_key is unique when present
        Schema::table('payouts', function (Blueprint $table) {
            $table->unique('idempotency_key', 'payouts_idempotency_key_unique');
        });

        // Extend status support beyond the original enum where needed.
        // If the underlying database uses ENUM (MySQL), convert it to VARCHAR for extensibility.
        $driver = Schema::getConnection()->getDriverName();
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payouts MODIFY status VARCHAR(32) NOT NULL");
        }
    }

    public function down(): void
    {
        // Best-effort rollback; do not attempt to restore enum type.
        Schema::table('payouts', function (Blueprint $table) {
            $table->dropUnique('payouts_idempotency_key_unique');
            $table->dropColumn([
                'provider',
                'method',
                'provider_reference',
                'idempotency_key',
                'approved_at',
                'failure_code',
                'failure_message',
                'method_details',
            ]);
        });
    }
};

