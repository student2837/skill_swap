<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchSkillRequest;
use App\Http\Requests\StoreSkillRequest;
use App\Http\Requests\UpdateSkillRequest;
use App\Models\Skill;
use App\Models\SkillRequest;
use App\Models\User;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SkillController extends Controller
{

    public function createSkill(StoreSkillRequest $request)
    {
        try{
        $validatedData = $request->validated();
        $validatedData['user_id']=Auth::id();
        // Set default status as 'draft' if not provided
        if (!isset($validatedData['status'])) {
            $validatedData['status'] = 'draft';
        }
        
        // Extract category_id and remove it from validated data (not a skill column)
        $categoryId = $validatedData['category_id'];
        unset($validatedData['category_id']);
        
        // Create skill
        $skill = Skill::create($validatedData);
        
        // Attach category via pivot table
        $skill->categories()->attach($categoryId);
        
        // Load category relationship for response
        $skill->load('categories');
        
        return response()->json([
            'message' => 'Skill created successfully',
            'skill' => $skill
        ], 201);

        }
        catch(Exception $e){
        return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    public function updateSkill(UpdateSkillRequest $request,$id){
        try{
        $skill=Skill::findOrFail($id);
        if($skill->user_id != Auth::id())
            return response()->json(['error' => 'Unauthorized'], 403);

        $validatedData = $request->validated();
        
        // Extract category_id and remove it from validated data
        $categoryId = $validatedData['category_id'];
        unset($validatedData['category_id']);
        
        // Update skill
        $skill->update($validatedData);
        
        // Sync category (replace all existing categories with the new one)
        $skill->categories()->sync([$categoryId]);
        
        // Load category relationship for response
        $skill->load('categories');
        
        return response()->json([
                'message' => 'Skill updated successfully',
                'skill' => $skill
            ]);
        }
        catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    public function changeStatus(Request $request, $id){
        try{
        $request->validate([
            'status' => 'required|in:draft,active,paused'
        ]);

        $skill=Skill::findOrFail($id);
        if($skill->user_id != Auth::id())
            return response()->json(['error' => 'Unauthorized'], 403);

        $skill->update(['status' => $request->status]);
        return response()->json([
                'message' => 'Skill status updated successfully',
                'skill' => $skill
            ]);
        }
        catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Skill not found'], 404);
        }
        catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    public function searchSkill(SearchSkillRequest $request){
    try {
        $query = $request->input('query');
        $category = $request->input('category');
        $minRating = $request->input('min_rating');
        $userId = Auth::id(); // Get authenticated user ID, null if not authenticated

        $skillsQuery = Skill::where('status', 'active')
            ->where(function($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%");
            });
        
        // Filter by category if provided (can be category ID or name)
        if ($category !== null && $category !== '') {
            // Check if it's a numeric ID
            if (is_numeric($category)) {
                $skillsQuery->whereHas('categories', function($q) use ($category) {
                    $q->where('categories.id', $category);
                });
            } else {
                // Otherwise treat as category name
                $skillsQuery->whereHas('categories', function($q) use ($category) {
                    $q->where('categories.name', $category);
                });
            }
        }
        
        // If user is authenticated, exclude their own skills
        if ($userId) {
            $skillsQuery->where('user_id', '!=', $userId);
        }

        // Filter by minimum skill rating (skill-specific rating)
        if ($minRating !== null && $minRating !== '') {
            $minRatingFloat = (float) $minRating;
            $skillsQuery->where('rating_avg', '>=', $minRatingFloat);
        }

        $skills = $skillsQuery->with(['user:id,name,rating_avg,is_verified', 'categories:id,name'])
            ->get()
            ->map(function($skill) {
                // Count students
                try {
                    $studentsCount = SkillRequest::where('skill_id', $skill->id)
                        ->whereIn('status', ['pending', 'accepted', 'completed'])
                        ->distinct()
                        ->count('student_id');
                    $skill->students_count = $studentsCount;
                } catch (\Exception $e) {
                    $skill->students_count = 0;
                }
                return $skill;
            });

        return response()->json([
            'message' => 'Search results',
            'skills' => $skills
        ]);

    }
    catch (Exception $e) {
        return response()->json(['error' => 'Something went wrong'], 500);
    }
}

    public function getSkillsByCategory(Request $request){
        try {
            $category = $request->input('category');
            $minRating = $request->input('min_rating');
            $userId = Auth::id(); // Get authenticated user ID, null if not authenticated
            
            // Validate category exists (can be ID or name)
            $categoryModel = null;
            if (is_numeric($category)) {
                $categoryModel = \App\Models\Category::find($category);
            } else {
                $categoryModel = \App\Models\Category::where('name', $category)->first();
            }
            
            if (!$categoryModel) {
                return response()->json(['error' => 'Category not found'], 404);
            }

            $skillsQuery = Skill::where('status', 'active')
                ->whereHas('categories', function($q) use ($categoryModel) {
                    $q->where('categories.id', $categoryModel->id);
                });
            
            // If user is authenticated, exclude their own skills
            if ($userId) {
                $skillsQuery->where('user_id', '!=', $userId);
            }

            // Filter by minimum skill rating (skill-specific rating)
            if ($minRating !== null && $minRating !== '') {
                $minRatingFloat = (float) $minRating;
                $skillsQuery->where('rating_avg', '>=', $minRatingFloat);
            }

            $skills = $skillsQuery->with(['user:id,name,rating_avg,is_verified', 'categories:id,name'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($skill) {
                    // Count students
                    try {
                        $studentsCount = SkillRequest::where('skill_id', $skill->id)
                            ->whereIn('status', ['pending', 'accepted', 'completed'])
                            ->distinct()
                            ->count('student_id');
                        $skill->students_count = $studentsCount;
                    } catch (\Exception $e) {
                        $skill->students_count = 0;
                    }
                    return $skill;
                });

            return response()->json([
                'message' => 'Skills retrieved successfully',
                'skills' => $skills
            ], 200);
        }
        catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    public function listAllSkills(Request $request){
        try {
            $userId = Auth::id(); // Get authenticated user ID, null if not authenticated
            $minRating = $request->input('min_rating');
            
            $query = Skill::where('status', 'active');
            
            // If user is authenticated, exclude their own skills
            if ($userId) {
                $query->where('user_id', '!=', $userId);
            }

            // Filter by minimum skill rating (skill-specific rating)
            if ($minRating !== null && $minRating !== '') {
                $minRatingFloat = (float) $minRating;
                $query->where('rating_avg', '>=', $minRatingFloat);
            }
            
            $skills = $query->with('user:id,name,rating_avg,is_verified')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($skill) {
                    // Count students (all requests - pending, accepted, completed)
                    try {
                        $studentsCount = SkillRequest::where('skill_id', $skill->id)
                            ->whereIn('status', ['pending', 'accepted', 'completed'])
                            ->distinct()
                            ->count('student_id');
                        $skill->students_count = $studentsCount;
                    } catch (\Exception $e) {
                        $skill->students_count = 0;
                    }
                    return $skill;
                });

            return response()->json([
                'message' => 'Skills retrieved successfully',
                'skills' => $skills
            ], 200);
        }
        catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    public function getSkill(Request $request, $id){
        try {
            // This endpoint is public, but if a Bearer token is present we still want
            // to detect the authenticated user to support "Booked" UI states.
            $authUser = $request->user('sanctum') ?? $request->user() ?? Auth::user();
            $userId = $authUser?->id; // null if not authenticated
            
            // First try to find the skill without status filter to see if it exists
            $skill = Skill::find($id);

            if (!$skill) {
                return response()->json(['error' => 'Skill not found'], 404);
            }

            $isOwner = $userId && ((int) $skill->user_id === (int) $userId);

            // Only owners can view non-active skills.
            // Public browsing should still show active-only.
            if (!$isOwner && $skill->status !== 'active') {
                return response()->json(['error' => 'Skill is not available'], 404);
            }

            // Load relationships with error handling
            try {
                $skill->load('user:id,name,bio,profile_pic,rating_avg,is_verified');
            } catch (\Exception $e) {
                // If user loading fails, set user to null
                $skill->setRelation('user', null);
                \Log::warning('Failed to load user for skill: ' . $skill->id, ['error' => $e->getMessage()]);
            }

            try {
                $skill->load('categories:id,name');
            } catch (\Exception $e) {
                // If categories loading fails, set categories to empty collection
                $skill->setRelation('categories', collect([]));
                \Log::warning('Failed to load categories for skill: ' . $skill->id, ['error' => $e->getMessage()]);
            }

            // Count students (all requests - pending, accepted, completed) 
            // Once a student requests, they count as a student and the count doesn't decrease
            try {
                $studentsCount = SkillRequest::where('skill_id', $skill->id)
                    ->whereIn('status', ['pending', 'accepted', 'completed'])
                    ->distinct()
                    ->count('student_id');
                $skill->students_count = $studentsCount;
            } catch (\Exception $e) {
                // If counting fails, set to 0
                $skill->students_count = 0;
                \Log::warning('Failed to count students for skill: ' . $skill->id, ['error' => $e->getMessage()]);
            }

            // Booking state for current authenticated user (used by "Book Now" button)
            // available: can book now
            // booked: has a pending request
            // locked: has ever been accepted/completed for this skill (never rebook)
            if ($userId) {
                try {
                    $statuses = SkillRequest::where('skill_id', $skill->id)
                        ->where('student_id', $userId)
                        ->pluck('status');

                    if ($statuses->contains('accepted') || $statuses->contains('completed')) {
                        $skill->booking_state = 'locked';
                        $skill->my_request_status = 'accepted';
                    } elseif ($statuses->contains('pending')) {
                        $skill->booking_state = 'booked';
                        $skill->my_request_status = 'pending';
                    } else {
                        $skill->booking_state = 'available';
                        $latest = SkillRequest::where('skill_id', $skill->id)
                            ->where('student_id', $userId)
                            ->latest()
                            ->value('status');
                        $skill->my_request_status = $latest ?: null;
                    }
                } catch (\Exception $e) {
                    $skill->booking_state = 'available';
                    $skill->my_request_status = null;
                }
            } else {
                $skill->booking_state = 'guest';
                $skill->my_request_status = null;
            }

            return response()->json([
                'message' => 'Skill retrieved successfully',
                'skill' => $skill
            ], 200);
        }
        catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Skill not found'], 404);
        }
        catch (Exception $e) {
            // Log the actual error for debugging
            \Log::error('Error in getSkill: ' . $e->getMessage(), [
                'exception' => $e,
                'skill_id' => $id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Something went wrong',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteSkill($id)
    {
        try {
            $skill = Skill::findOrFail($id);

            if ($skill->user_id !== Auth::id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            $skill->delete();

            return response()->json(['message' => 'Skill deleted successfully']);

        }
        catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    public function addCategoriesToSkill(Request $request, $skillId){
    try {

        $request->validate([
            'category_ids' => 'required|array',
            'category_ids.*' => 'integer|exists:categories,id'
        ]);

        $skill = Skill::findOrFail($skillId);
        $skill->categories()->syncWithoutDetaching($request->category_ids);
        return response()->json([
            'message' => 'Categories attached successfully',
            'skill' => $skill->load('categories')
        ], 200);

    }
    catch (ModelNotFoundException $e) {
        return response()->json(['error' => 'Skill not found'], 404);
    }
    catch (QueryException $e) {
        return response()->json(['error' => 'Database error'], 400);
    }
    catch (Exception $e) {
        return response()->json(['error' => 'Something went wrong'], 500);
    }
}

public function getSkillCategories($skillId){
    try{
    $skill=Skill::findOrFail($skillId);
    $categories=$skill->categories;
    return response()->json([
        'message'=>'Skill categories retrieved successfully',
        'categories'=>$categories
    ],200);
    }
    catch (ModelNotFoundException $e) {
        return response()->json(['error' => 'Skill not found'], 404);
    }
    catch (Exception $e) {
        return response()->json(['error' => 'Something went wrong'], 500);
    }
}

public function getSkillStudents($skillId)
{
    try {
        $userId = Auth::id(); // currently logged-in user (teacher)

        // find the skill and ensure the logged-in user owns it
        $skill = Skill::where('id', $skillId)
                    ->where('user_id', $userId)
                    ->firstOrFail();

        // get students using the relation we defined
        $students = $skill->students()->get(['users.id', 'users.name', 'users.email']);

        return response()->json([
            'message'  => 'Students retrieved successfully',
            'skill'    => $skill->title,
            'students' => $students
        ], 200);

    } catch (ModelNotFoundException $e) {
        return response()->json(['error' => 'Skill not found or not owned by you'], 404);
    } catch (Exception $e) {
        return response()->json(['error' => 'Something went wrong'], 500);
    }
}

    public function getStatistics()
    {
        try {
            // Count total users (excluding admins, or including all - you can adjust)
            $totalUsers = User::where('is_admin', false)->count();
            
            // Count active skills
            $activeSkills = Skill::where('status', 'active')->count();
            
            return response()->json([
                'message' => 'Statistics retrieved successfully',
                'statistics' => [
                    'total_users' => $totalUsers,
                    'active_skills' => $activeSkills
                ]
            ]);
        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
}
