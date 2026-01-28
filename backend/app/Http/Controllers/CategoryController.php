<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;


class CategoryController extends Controller
{


    public function createCategory(Request $request)
    {
        try {
            // Ensure user is admin (defense in depth)
            if (!Auth::user()->is_admin) {
                return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
            }

            $request->validate([
                'name' => 'required|string|unique:categories,name|max:100'
            ]);

            $category = Category::create([
                'name' => $request->name
            ]);

            return response()->json([
                'message' => 'Category created successfully',
                'category' => $category
            ], 201);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    public function deleteCategory($id)
    {
        try {
            // Ensure user is admin (defense in depth)
            if (!Auth::user()->is_admin) {
                return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
            }

            $category = Category::findOrFail($id);
            $category->delete();

            return response()->json([
                'message' => 'Category deleted successfully'
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Category not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    public function listCategories()
    {
        try {
            $categories = Category::all();
            
            // Sort categories: alphabetically first, then "other" (case-insensitive) should always be last
            $sortedCategories = $categories->sortBy(function($category) {
                $name = strtolower($category->name);
                // If it's "other", return a high value to push it to the end
                // Otherwise, return the lowercase name for alphabetical sorting
                return $name === 'other' ? 'zzzzz_other' : $name;
            })->values();

            return response()->json([
                'message' => 'Categories retrieved successfully',
                'categories' => $sortedCategories
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }



    public function getCategorySkills($categoryId){
        try{
            $category = Category::findOrFail($categoryId);
            $skills = $category->skills()
                ->where('status', 'active')
                ->with('user:id,name')
                ->get();
            
            return response()->json([
                'message' => 'Skills of this category retrieved successfully',
                'skills' => $skills
            ], 200);
        }
        catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Category not found'], 404);
        }
        catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
}

