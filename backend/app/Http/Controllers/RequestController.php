<?php

namespace App\Http\Controllers;
use App\Models\SkillRequest;
use App\Models\Skill;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class RequestController extends Controller
{
    public function createRequest($skillId)
    {
        try {
            // Ensure user is authenticated (middleware should handle this, but double-check)
            if (!Auth::check()) {
                return response()->json(['error' => 'Unauthenticated. Please log in to request a session.'], 401);
            }

            $skill = Skill::findOrFail($skillId);
            $userId = Auth::id();

            // Additional safety check
            if (!$userId) {
                return response()->json(['error' => 'User not found. Please log in again.'], 401);
            }

            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'User not found. Please log in again.'], 401);
            }

            // Prevent users from requesting their own skills
            if ($skill->user_id === $userId) {
                return response()->json(['error' => 'You cannot request a session for your own skill'], 403);
            }

            // Booking rules:
            // - If there was ever an accepted/completed request for this student + skill => never allow rebook
            // - If there is a pending request for this student + skill => do not allow rebook (button should show "Booked")
            // - Allow rebook only if the latest attempts were cancelled/rejected (or no previous requests)
            DB::beginTransaction();
            try {
                $existing = SkillRequest::where('student_id', $userId)
                    ->where('skill_id', $skill->id)
                    ->lockForUpdate()
                    ->get(['id', 'status']);

                $hasAccepted = $existing->contains(function ($r) {
                    return in_array($r->status, ['accepted', 'completed'], true);
                });
                if ($hasAccepted) {
                    DB::rollBack();
                    return response()->json([
                        'error' => 'You already booked this skill and were accepted. You cannot book it again.',
                        'code' => 'BOOKING_LOCKED',
                        'status' => 'accepted',
                    ], 409);
                }

                $hasPending = $existing->contains(function ($r) {
                    return $r->status === 'pending';
                });
                if ($hasPending) {
                    DB::rollBack();
                    return response()->json([
                        'error' => 'You already booked this skill.',
                        'code' => 'ALREADY_BOOKED',
                        'status' => 'pending',
                    ], 409);
                }

            // Ensure student has enough credits before creating the request
            // (Skill details UI will redirect to buy credits on this error.)
            $skillPrice = (int) ($skill->price ?? 0);
            $userCredits = (int) ($user->credits ?? 0);
            if ($skillPrice > 0 && $userCredits < $skillPrice) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Insufficient credits to book this skill.',
                    'required_credits' => $skillPrice,
                    'current_credits' => $userCredits,
                    'missing_credits' => max(0, $skillPrice - $userCredits),
                ], 400);
            }

            $request = SkillRequest::create([
                'student_id' => $userId,
                'skill_id'   => $skill->id,
                'status'     => 'pending'
            ]);

            // Automatically create a conversation between student and teacher
            \App\Models\Conversation::getOrCreate(
                $userId,
                $skill->user_id,
                $request->id
            );

                DB::commit();
            return response()->json([
                'message' => 'Request created successfully',
                'request' => $request
            ], 201);
            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        }
        catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Skill not found'], 404);
        }
        catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    // Teacher → accept request
    public function acceptRequest($id)
    {
        try {
            $req = SkillRequest::with('skill', 'student')->findOrFail($id);

            if ($req->skill->user_id !== Auth::id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Only pending requests can be accepted
            if ($req->status !== 'pending') {
                return response()->json(['error' => 'Only pending requests can be accepted'], 400);
            }

            $skill = $req->skill;
            $student = $req->student;
            $teacher = Auth::user();
            $creditAmount = $skill->price;

            // Check if student has enough credits
            if ($student->credits < $creditAmount) {
                return response()->json(['error' => 'Student does not have enough credits'], 400);
            }

            // Use database transaction to ensure atomicity
            DB::beginTransaction();

            try {
                // Deduct credits from student
                $student->decrement('credits', $creditAmount);
                
                // Add credits to teacher
                $teacher->increment('credits', $creditAmount);

                // Create transaction for student (payment)
                Transaction::create([
                    'user_id' => $student->id,
                    'type' => 'skill_payment',
                    'amount' => $creditAmount,
                    'fee' => 0,
                    'status' => 'completed',
                    'reference_id' => 'request_' . $req->id
                ]);

                // Create transaction for teacher (earning)
                Transaction::create([
                    'user_id' => $teacher->id,
                    'type' => 'skill_earning',
                    'amount' => $creditAmount,
                    'fee' => 0,
                    'status' => 'completed',
                    'reference_id' => 'request_' . $req->id
                ]);

                // Update request status
            $req->update(['status' => 'accepted']);

                // Ensure conversation exists (it should already exist from createRequest, but ensure it's there)
                \App\Models\Conversation::getOrCreate(
                    $student->id,
                    $teacher->id,
                    $req->id
                );

                DB::commit();

            return response()->json([
                'message' => 'Request accepted successfully',
                    'request' => $req->fresh(['skill', 'student'])
            ], 200);

            } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        }
        catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Request not found'], 404);
        }
        catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }


    public function rejectRequest($id)
    {
        try {
            $req = SkillRequest::with('skill')->findOrFail($id);

            if ($req->skill->user_id !== Auth::id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Only pending requests can be rejected
            if ($req->status !== 'pending') {
                return response()->json(['error' => 'Only pending requests can be rejected'], 400);
            }

            $req->update(['status' => 'rejected']);

            return response()->json([
                'message' => 'Request rejected successfully',
                'request' => $req
            ], 200);

        }
        catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Request not found'], 404);
        }
        catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }


    public function cancelRequest($id)
    {
        try {
            $req = SkillRequest::findOrFail($id);

            if ($req->student_id !== Auth::id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Only pending requests can be cancelled by the student
            if ($req->status !== 'pending') {
                return response()->json(['error' => 'Only pending requests can be cancelled'], 400);
            }

            $req->update(['status' => 'cancelled']);

            return response()->json([
                'message' => 'Request cancelled successfully',
                'request' => $req
            ], 200);

        }
        catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Request not found'], 404);
        }
        catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    public function completeRequest($id)
    {
        try {
            $req = SkillRequest::with('skill')->findOrFail($id);

            if ($req->skill->user_id !== Auth::id()) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Only accepted requests can be completed
            if ($req->status !== 'accepted') {
                return response()->json(['error' => 'Only accepted requests can be marked as completed'], 400);
            }

            $req->update(['status' => 'completed']);

            return response()->json([
                'message' => 'Request marked as completed',
                'request' => $req
            ], 200);

        }
        catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Request not found'], 404);
        }
        catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    /**
     * Get teaching requests (requests for skills the user teaches)
     */
    public function getTeachingRequests()
    {
        try {
            $user = Auth::user();
            
            // Get all requests for skills that belong to this user
            $requests = SkillRequest::whereHas('skill', function($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['student:id,name,email', 'skill:id,title,price'])
            ->orderBy('created_at', 'desc')
            ->get();

            return response()->json([
                'message' => 'Teaching requests retrieved successfully',
                'requests' => $requests
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    /**
     * Get learning requests (requests the user made as a student)
     */
    public function getLearningRequests()
    {
        try {
            $user = Auth::user();
            
            // Get all requests made by this user
            $requests = SkillRequest::where('student_id', $user->id)
                ->with(['skill:id,title,price,user_id', 'skill.user:id,name'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'message' => 'Learning requests retrieved successfully',
                'requests' => $requests
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    /**
     * Permanently delete cancelled learning requests for the authenticated user.
     */
    public function purgeCancelledLearningRequests()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $deleted = SkillRequest::where('student_id', $user->id)
                ->where('status', 'cancelled')
                ->delete();

            return response()->json([
                'message' => 'Cancelled learning requests deleted successfully',
                'deleted' => $deleted
            ], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    /**
     * Permanently delete cancelled teaching requests for skills owned by the authenticated user.
     */
    public function purgeCancelledTeachingRequests()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'Unauthenticated'], 401);
            }

            $deleted = SkillRequest::where('status', 'cancelled')
                ->whereHas('skill', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                ->delete();

            return response()->json([
                'message' => 'Cancelled teaching requests deleted successfully',
                'deleted' => $deleted
            ], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
}

