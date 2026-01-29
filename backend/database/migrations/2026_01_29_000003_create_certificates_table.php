<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->nullable()->constrained('requests')->nullOnDelete();
            $table->foreignId('skill_id')->nullable()->constrained('skills')->nullOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('course_name');
            $table->string('teacher_name');
            $table->string('student_name');
            $table->string('certificate_code', 32)->unique();
            $table->unsignedInteger('score')->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->boolean('passed')->default(true);
            $table->text('certificate_text')->nullable();
            $table->date('completion_date')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'completion_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
