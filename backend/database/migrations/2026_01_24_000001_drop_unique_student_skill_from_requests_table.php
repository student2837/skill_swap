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
        // MySQL can use the unique index to enforce FK constraints.
        // Drop FKs first, then drop the unique index, then re-add FKs.
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropForeign(['skill_id']);
        });

        Schema::table('requests', function (Blueprint $table) {
            // Allow a student to request/book the same skill multiple times
            $table->dropUnique(['student_id', 'skill_id']);
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('skill_id')->references('id')->on('skills')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropForeign(['student_id']);
            $table->dropForeign(['skill_id']);
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->unique(['student_id', 'skill_id']);
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->foreign('student_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('skill_id')->references('id')->on('skills')->cascadeOnDelete();
        });
    }
};

