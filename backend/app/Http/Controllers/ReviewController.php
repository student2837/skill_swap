<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\SkillRequest;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;

class ReviewController extends Controller
{
    /**
     * Create a new review
     */
    public function createReview(Request $request)
    {
        try {
            $request->validate([
                'request_id' => 'required|exists:requests,id',
                'to_user_id' => 'required|exists:users,id',
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string|max:500'
            ]);

            $user = Auth::user();

            // Check if user is involved in this request (as teacher or student)
            $skillRequest = SkillRequest::findOrFail($request->request_id);
            if ($skillRequest->student_id != $user->id && $skillRequest->skill->user_id != $user->id) {
                return response()->json(['error' => 'Unauthorized to review this request'], 403);
            }

            $review = Review::create([
                'request_id' => $request->request_id,
                'from_user_id' => $user->id,
                'to_user_id' => $request->to_user_id,
                'rating' => $request->rating,
                'comment' => $request->comment
            ]);

            // Update the skill's average rating
            $skill = $skillRequest->skill;
            if ($skill) {
                $this->updateSkillRating($skill->id);
            }

            // Update the teacher's average rating
            $teacher = User::find($request->to_user_id);
            if ($teacher) {
                $this->updateTeacherRating($teacher->id);
            }

            return response()->json([
                'message' => 'Review added successfully',
                'review' => $review
            ], 201);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    /**
     * Update existing review
     */
    public function updateReview(Request $request, $id)
    {
        try {
            $review = Review::findOrFail($id);
            $user = Auth::user();

            // Only the author of the review can update it
            if ($review->from_user_id != $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $request->validate([
                'rating' => 'integer|min:1|max:5',
                'comment' => 'nullable|string|max:500'
            ]);

            // Load the request relationship before updating
            $review->load('request');
            $skillId = $review->request ? $review->request->skill_id : null;
            $teacherId = $review->to_user_id;

            $review->update($request->only(['rating', 'comment']));

            // Update the skill's average rating
            if ($skillId) {
                $this->updateSkillRating($skillId);
            }

            // Update the teacher's average rating
            if ($teacherId) {
                $this->updateTeacherRating($teacherId);
            }

            return response()->json([
                'message' => 'Review updated successfully',
                'review' => $review
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    /**
     * Get all reviews for a specific user (teacher or student)
     */
    public function getReviewsForUser($userId)
    {
        try {
            $reviews = Review::where('to_user_id', $userId)
                ->with('fromUser:id,name,profile_pic')  // reviewer info
                ->get();

            return response()->json([
                'reviews' => $reviews
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    /**
     * Calculate average rating for a specific user
     */
    public function getAverageRating($userId)
    {
        try {
            $average = Review::where('to_user_id', $userId)->avg('rating');

            return response()->json([
                'average_rating' => round($average, 2)
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    /**
     * Get reviews for a specific skill
     */
    public function getReviewsForSkill($skillId)
    {
        try {
            // Get all reviews for the teacher of this skill
            $skill = \App\Models\Skill::findOrFail($skillId);
            $teacherId = $skill->user_id;
            
            $reviews = Review::where('to_user_id', $teacherId)
                ->whereHas('request', function($query) use ($skillId) {
                    $query->where('skill_id', $skillId);
                })
                ->with(['fromUser:id,name,profile_pic'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($review) {
                    // Ensure fromUser is loaded, if not, try to load it
                    if (!$review->fromUser) {
                        $review->load('fromUser:id,name,profile_pic');
                    }
                    return $review;
                });

            return response()->json([
                'reviews' => $reviews
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    /**
     * Get completed requests that the user can review (as a student)
     */
    public function getReviewableRequests()
    {
        try {
            $user = Auth::user();
            
            // Get completed learning requests where user is the student
            $requests = SkillRequest::where('student_id', $user->id)
                ->where('status', 'completed')
                ->with([
                    'skill:id,title,price,user_id',
                    'skill.user:id,name'
                ])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($request) use ($user) {
                    // Check if user already reviewed this request
                    $existingReview = Review::where('request_id', $request->id)
                        ->where('from_user_id', $user->id)
                        ->first();
                    
                    $request->already_reviewed = $existingReview !== null;
                    return $request;
                });

            return response()->json([
                'message' => 'Reviewable requests retrieved successfully',
                'requests' => $requests
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    /**
     * Update the average rating for a specific skill
     */
    private function updateSkillRating($skillId)
    {
        try {
            // Get all reviews for requests that belong to this skill
            $averageRating = Review::whereHas('request', function($query) use ($skillId) {
                $query->where('skill_id', $skillId);
            })->avg('rating');

            // Update the skill's rating_avg
            $skill = Skill::find($skillId);
            if ($skill) {
                $skill->update(['rating_avg' => round($averageRating ?? 0, 2)]);
            }
        } catch (Exception $e) {
            // Log error but don't fail the request
            \Log::error('Failed to update skill rating: ' . $e->getMessage());
        }
    }

    /**
     * Update the average rating for a specific teacher
     */
    private function updateTeacherRating($teacherId)
    {
        try {
            $averageRating = Review::where('to_user_id', $teacherId)->avg('rating');
            
            $teacher = User::find($teacherId);
            if ($teacher) {
                $teacher->update(['rating_avg' => round($averageRating ?? 0, 2)]);
            }
        } catch (Exception $e) {
            // Log error but don't fail the request
            \Log::error('Failed to update teacher rating: ' . $e->getMessage());
        }
    }
}
