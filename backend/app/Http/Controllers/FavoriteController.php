<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Skill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class FavoriteController extends Controller
{
    /**
     * Add a favorite (skill or user)
     */
    public function addFavorite(Request $request)
    {
        try {
            $request->validate([
                'target_user_id' => 'nullable|exists:users,id',
                'skill_id' => 'nullable|exists:skills,id',
            ]);

            if (!$request->target_user_id && !$request->skill_id) {
                return response()->json(['error' => 'Please provide either target_user_id or skill_id'], 400);
            }

            $user = Auth::user();

            if ($request->target_user_id && $request->target_user_id == $user->id) {
                return response()->json(['error' => 'You cannot favorite yourself'], 400);
            }

            // Prevent users from favoriting their own skills
            if ($request->skill_id) {
                $skill = Skill::find($request->skill_id);
                if ($skill && $skill->user_id === $user->id) {
                    return response()->json(['error' => 'You cannot add your own skill to favorites'], 400);
                }
            }

            $exists = Favorite::where('user_id', $user->id)
                ->where('target_user_id', $request->target_user_id)
                ->where('skill_id', $request->skill_id)
                ->exists();

            if ($exists) {
                return response()->json(['message' => 'Already in favorites'], 200);
            }

            Favorite::create([
                'user_id' => $user->id,
                'target_user_id' => $request->target_user_id,
                'skill_id' => $request->skill_id,
            ]);

            return response()->json(['message' => 'Added to favorites successfully'], 201);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    /**
     * Remove a favorite
     */
    public function removeFavorite(Request $request)
    {
        try {
            $request->validate([
                'target_user_id' => 'nullable|exists:users,id',
                'skill_id' => 'nullable|exists:skills,id',
            ]);

            $user = Auth::user();

            $favorite = Favorite::where('user_id', $user->id)
                ->where('target_user_id', $request->target_user_id)
                ->where('skill_id', $request->skill_id)
                ->first();

            if (!$favorite) {
                return response()->json(['error' => 'Favorite not found'], 404);
            }

            $favorite->delete();

            return response()->json(['message' => 'Removed from favorites successfully'], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    /**
     * List all favorites for the authenticated user
     */
    public function listFavorites()
    {
        try {
            $user = Auth::user();

            $favorites = Favorite::with([
                    'skill:id,title,description,shortDesc,price,lesson_type,category,user_id',
                    'skill.user:id,name,profile_pic,rating_avg'
                ])
                ->where('user_id', $user->id)
                ->get();

            return response()->json([
                'message' => 'Favorites retrieved successfully',
                'favorites' => $favorites
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
}
