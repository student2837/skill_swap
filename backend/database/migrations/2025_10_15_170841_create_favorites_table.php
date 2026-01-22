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
        Schema::create('favorites', function (Blueprint $table) {
        $table->id();
        $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // who favorites
        $table->foreignId('target_user_id')->nullable()->constrained('users')->cascadeOnDelete(); // who is favorited (optional)
        $table->foreignId('skill_id')->nullable()->constrained('skills')->cascadeOnDelete(); // skill favorited (optional)
        $table->timestamps();
        // prevent duplicates
        $table->unique(['user_id', 'target_user_id', 'skill_id']);
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
