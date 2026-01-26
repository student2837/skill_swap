<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\SkillRequest;
use App\Models\Skill;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
                ->with([
                    'fromUser:id,name,profile_pic',
                    'request.skill:id,title,status,user_id'
                ])  // reviewer + skill info
                ->orderBy('created_at', 'desc')
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
     * Skill performance summary for the authenticated teacher.
     * Returns per-skill: sessions_count, avg_rating, ratings_count, credits_earned, status.
     */
    public function getSkillPerformance()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $skills = Skill::where('user_id', $user->id)
                ->select('id', 'title', 'status')
                ->orderBy('created_at', 'desc')
                ->get();

            if ($skills->isEmpty()) {
                return response()->json(['skills' => []], 200);
            }

            $skillIds = $skills->pluck('id')->all();

            // Sessions: count accepted/completed requests per skill
            $sessionsBySkill = SkillRequest::whereIn('skill_id', $skillIds)
                ->whereIn('status', ['accepted', 'completed'])
                ->select('skill_id', DB::raw('COUNT(*) as sessions_count'))
                ->groupBy('skill_id')
                ->pluck('sessions_count', 'skill_id');

            // Ratings: avg + count per skill (reviews written about this teacher, grouped by skill)
            $ratingsRows = Review::join('requests', 'requests.id', '=', 'reviews.request_id')
                ->whereIn('requests.skill_id', $skillIds)
                ->where('reviews.to_user_id', $user->id)
                ->select(
                    'requests.skill_id as skill_id',
                    DB::raw('AVG(reviews.rating) as avg_rating'),
                    DB::raw('COUNT(reviews.id) as ratings_count')
                )
                ->groupBy('requests.skill_id')
                ->get()
                ->keyBy('skill_id');

            // Credits earned: sum skill_earning transactions per skill via request reference_id
            $creditsBySkill = Transaction::join('requests', DB::raw('transactions.reference_id'), '=', DB::raw("CONCAT('request_', requests.id)"))
                ->whereIn('requests.skill_id', $skillIds)
                ->where('transactions.user_id', $user->id)
                ->where('transactions.type', 'skill_earning')
                ->where('transactions.status', 'completed')
                ->select('requests.skill_id as skill_id', DB::raw('SUM(transactions.amount) as credits_earned'))
                ->groupBy('requests.skill_id')
                ->pluck('credits_earned', 'skill_id');

            $result = $skills->map(function ($skill) use ($sessionsBySkill, $ratingsRows, $creditsBySkill) {
                $sessions = (int) ($sessionsBySkill[$skill->id] ?? 0);
                $avgRating = $ratingsRows->has($skill->id) ? (float) ($ratingsRows[$skill->id]->avg_rating ?? 0) : 0.0;
                $ratingsCount = $ratingsRows->has($skill->id) ? (int) ($ratingsRows[$skill->id]->ratings_count ?? 0) : 0;
                $credits = (float) ($creditsBySkill[$skill->id] ?? 0);

                return [
                    'skill_id' => $skill->id,
                    'skill_title' => $skill->title ?? 'Untitled',
                    'status' => $skill->status ?? 'draft',
                    'sessions_count' => $sessions,
                    'avg_rating' => $ratingsCount > 0 ? round($avgRating, 2) : 0,
                    'ratings_count' => $ratingsCount,
                    'credits_earned' => $credits,
                ];
            })->values();

            return response()->json(['skills' => $result], 200);
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
