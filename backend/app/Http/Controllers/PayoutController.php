<?php

namespace App\Http\Controllers;

use App\Models\Payout;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

class PayoutController extends Controller
{
    /**
     * User requests payout
     */
    public function requestPayout(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|integer|min:1'
            ]);

            $user = Auth::user();

            if ($user->credits < $request->amount) {
                return response()->json(['error' => 'Insufficient credits'], 400);
            }

            // Use database transaction to ensure atomicity
            DB::beginTransaction();

            try {
                // Deduct credits immediately when payout is requested
                $user->decrement('credits', $request->amount);

                // Create transaction record (pending status)
                Transaction::create([
                    'user_id' => $user->id,
                    'type' => 'cashout',
                    'amount' => $request->amount,
                    'fee' => 0,
                    'status' => 'pending',
                    'reference_id' => null // Will be set when payout is created
                ]);

                // Create payout request
            $payout = Payout::create([
                'user_id' => $user->id,
                'amount' => $request->amount,
                'status' => 'pending'
            ]);

                // Update transaction with payout reference
                Transaction::where('user_id', $user->id)
                    ->where('type', 'cashout')
                    ->where('status', 'pending')
                    ->whereNull('reference_id')
                    ->latest()
                    ->first()
                    ->update(['reference_id' => 'payout_' . $payout->id]);

                DB::commit();

            return response()->json([
                'message' => 'Payout request submitted',
                'payout' => $payout
            ], 201);

        } catch (Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Admin approves payout
     */
    public function approvePayout($id)
    {
        try {
            // Ensure user is admin (defense in depth)
            if (!Auth::user()->is_admin) {
                return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
            }

            $payout = Payout::findOrFail($id);

            if ($payout->status !== 'pending') {
                return response()->json(['error' => 'Invalid payout status'], 400);
            }

            $payout->update([
                'status' => 'approved',
                'processed_at' => Carbon::now()
            ]);

            return response()->json([
                'message' => 'Payout approved',
                'payout' => $payout
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    /**
     * Admin rejects payout
     */
    public function rejectPayout(Request $request, $id)
    {
        try {
            // Ensure user is admin (defense in depth)
            if (!Auth::user()->is_admin) {
                return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
            }

            $request->validate([
                'admin_note' => 'required|string'
            ]);

            $payout = Payout::findOrFail($id);

            $payout->update([
                'status' => 'rejected',
                'admin_note' => $request->admin_note,
                'processed_at' => Carbon::now()
            ]);

            return response()->json([
                'message' => 'Payout rejected',
                'payout' => $payout
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    /**
     * Mark payout as paid (money sent)
     */
    public function markAsPaid($id)
    {
        try {
            // Ensure user is admin (defense in depth)
            if (!Auth::user()->is_admin) {
                return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
            }

            $payout = Payout::findOrFail($id);

            if ($payout->status !== 'approved') {
                return response()->json(['error' => 'Payout must be approved first'], 400);
            }

            // Update transaction status from pending to completed
            Transaction::where('reference_id', 'payout_' . $payout->id)
                ->where('status', 'pending')
                ->update(['status' => 'completed']);

            $payout->update([
                'status' => 'paid',
                'processed_at' => Carbon::now()
            ]);

            return response()->json([
                'message' => 'Payout completed',
                'payout' => $payout
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get payouts of logged-in user
     */
    public function getUserPayouts()
    {
        try {
            $payouts = Auth::user()->payouts()->latest()->get();

            return response()->json([
                'payouts' => $payouts
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    /**
     * Admin: Get all payouts
     */
    public function getAllPayouts()
    {
        try {
            // Ensure user is admin (defense in depth)
            if (!Auth::user()->is_admin) {
                return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
            }

            $payouts = Payout::with('user:id,name,email')
                ->latest()
                ->get();

            return response()->json([
                'payouts' => $payouts
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
}
