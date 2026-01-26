<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, create categories from existing enum values and link them to skills
        $enumCategories = ['music', 'programming', 'design', 'languages', 'other'];
        
        foreach ($enumCategories as $catName) {
            // Check if category already exists
            $exists = DB::table('categories')->where('name', $catName)->exists();
            if (!$exists) {
                DB::table('categories')->insert([
                    'name' => $catName,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        
        // Link existing skills to categories based on their enum value
        $skills = DB::table('skills')->whereNotNull('category')->get();
        foreach ($skills as $skill) {
            $category = DB::table('categories')->where('name', $skill->category)->first();
            if ($category) {
                // Check if link already exists
                $linkExists = DB::table('category_skill')
                    ->where('category_id', $category->id)
                    ->where('skill_id', $skill->id)
                    ->exists();
                
                if (!$linkExists) {
                    DB::table('category_skill')->insert([
                        'category_id' => $category->id,
                        'skill_id' => $skill->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
        
        // Now change the category column to nullable string (we'll keep it for backward compatibility temporarily)
        Schema::table('skills', function (Blueprint $table) {
            $table->string('category')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('skills', function (Blueprint $table) {
            $table->enum('category', ['music', 'programming', 'design', 'languages', 'other'])->change();
        });
    }
};
