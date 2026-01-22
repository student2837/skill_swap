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
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('skill_id')->constrained('skills')->cascadeOnDelete();
            $table->enum('status', ['pending','accepted','rejected','cancelled','completed'])->default('pending');
            $table->timestamps();
            $table->unique(['student_id', 'skill_id']); // student can't request same skill twice
        });
        }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requests');
    }
};
