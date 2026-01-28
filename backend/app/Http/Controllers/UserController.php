<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSkillRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Exception;
use App\Models\Skill;
use App\Models\SkillRequest;


class UserController extends Controller
{
    public function register(Request $request){
        try {
            $request->validate([
                'name'=>'required|string|max:50',
                'email'=>'required|email|unique:users,email',
                'password'=>'required|min:8',
            ]);

            $user=User::create([
                'name'=>$request->name,
                'email'=>$request->email,
                'password'=>Hash::make($request->password),
            ]);

            return response()->json([
                'message'=>'User registered successfully',
                'user'=>$user,
            ],201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Handle validation errors, especially duplicate email
            if ($e->errors() && isset($e->errors()['email'])) {
                return response()->json([
                    'error' => 'This email is already registered. Please use a different email or log in.',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }
    }

    public function login(Request $request){
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if(!Auth::attempt($request->only('email','password'))){
            return response()->json([
                'message' => 'invalid email or password'
                ],
                401);
        }

        $user=User::where('email',$request->email)->firstOrFail();
        $token=$user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
        ]);         
    }

    public function logout(Request $request){
        try {
            $user = $request->user();
            if ($user && $user->currentAccessToken()) {
                $user->currentAccessToken()->delete();
            }
            return response()->json(['message' => 'Logged out successfully']);
        } catch (Exception $e) {
            // Token might already be invalid/expired, but we still return success
            // to allow the client to clear their local token
            return response()->json(['message' => 'Logged out successfully']);
        }
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'name' => 'sometimes|string|max:50',
            'email' => 'sometimes|email|unique:users,email,' . $user->id,
            'bio' => 'sometimes|nullable|string',
            'profile_pic' => 'sometimes|nullable|string',
        ]);
        
        $user->update($request->only(['name', 'email', 'bio', 'profile_pic']));
        return response()->json($user);
    }

    public function getUser(Request $request)
    {
        return response()->json($request->user());
    }

    public function getTeachingSkills()
{
    try {
        $user = Auth::user();

        // Get ALL skills created by the user, not just those with students
        // Include rating_avg which is the skill-specific rating
        $skills = $user->skills()
                        ->with(['students:id,name'])
                        ->withCount('students')
                        ->orderBy('created_at', 'desc')
                        ->get();

        return response()->json([
            'message' => 'Teaching skills retrieved successfully',
            'skills'  => $skills
        ], 200);

    } catch (Exception $e) {
        return response()->json(['error' => 'Something went wrong'], 500);
    }
}

    public function getLearningSkills()
{
    try {
        $user = Auth::user();

        $skills = $user->learningSkills()->with('user:id,name')->get();

        return response()->json([
            'message' => 'Active learning skills retrieved successfully',
            'skills'  => $skills
        ], 200);

    } catch (Exception $e) {
        return response()->json(['error' => 'Something went wrong'], 500);
    }
}

    /**
     * Admin: Get all users
     */
    public function getAllUsers()
    {
        try {
            // Ensure user is admin (defense in depth)
            if (!Auth::user()->is_admin) {
                return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
            }

            $users = User::query()
                ->select('users.id', 'users.name', 'users.email', 'users.bio', 'users.credits', 'users.rating_avg', 'users.is_admin', 'users.is_verified', 'users.created_at')
                ->withCount('skills')
                ->selectSub(function ($q) {
                    $q->from('requests')
                        ->join('skills', 'requests.skill_id', '=', 'skills.id')
                        ->whereColumn('skills.user_id', 'users.id')
                        ->whereIn('requests.status', ['accepted', 'completed'])
                        ->selectRaw('COUNT(DISTINCT requests.student_id)');
                }, 'students_taught_count')
                ->latest()
                ->get();

            return response()->json([
                'users' => $users
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    /**
     * Admin: Set user verification (blue tick)
     */
    public function setUserVerified(Request $request, $id)
    {
        try {
            $admin = Auth::user();
            if (!$admin || !$admin->is_admin) {
                return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
            }

            $request->validate([
                'is_verified' => 'required|boolean',
            ]);

            $user = User::findOrFail($id);
            $user->is_verified = (bool) $request->is_verified;
            $user->save();

            return response()->json([
                'message' => 'User verification updated',
                'user' => $user->only(['id', 'name', 'email', 'is_admin', 'is_verified'])
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'User not found'], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request)
    {
        try {
            $request->validate([
                'current_password' => 'required',
                'new_password' => 'required|min:8',
            ]);

            $user = Auth::user();

            // Verify current password
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json(['error' => 'Current password is incorrect'], 400);
            }

            // Update password
            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json([
                'message' => 'Password changed successfully'
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete own account
     */
    public function deleteOwnAccount(Request $request)
    {
        try {
            $user = Auth::user();

            // Delete all user's tokens
            $user->tokens()->delete();

            // Delete the user
            $user->delete();

            return response()->json([
                'message' => 'Account deleted successfully'
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Admin: Delete a user
     */
    public function deleteUser($id)
    {
        try {
            // Ensure user is admin (defense in depth)
            $admin = Auth::user();
            if (!$admin->is_admin) {
                return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
            }

            // Prevent admin from deleting themselves
            if ($admin->id == $id) {
                return response()->json(['error' => 'You cannot delete your own account.'], 400);
            }

            $user = User::findOrFail($id);
            
            // Prevent deleting other admins (optional safety measure)
            // Uncomment if you want to prevent deleting other admins
            // if ($user->is_admin) {
            //     return response()->json(['error' => 'Cannot delete admin users.'], 400);
            // }

            $user->delete();

            return response()->json([
                'message' => 'User deleted successfully'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'User not found'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

}
