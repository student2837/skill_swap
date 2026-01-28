<?php

namespace App\Http\Controllers;

use App\Models\Payout;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use App\Jobs\ExecutePayoutJob;
use App\Services\PayoutService;
use Illuminate\Support\Str;

class PayoutController extends Controller
{
    /**
     * User requests payout
     */
    public function requestPayout(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|integer|min:10',
                'provider' => 'nullable|string|in:manual,paypal',
            ]);

            $user = Auth::user();

            $gross = (int) $request->amount;
            
            // Check minimum amount
            if ($gross < 10) {
                return response()->json(['error' => 'Minimum cashout amount is 10 credits'], 400);
            }
            
            $fee = $user->is_admin ? 0 : (int) floor($gross * 0.20);
            $net = max(0, $gross - $fee);

            if (($user->credits ?? 0) < $gross) {
                return response()->json(['error' => 'Insufficient credits'], 400);
            }
            if ($net <= 0) {
                return response()->json(['error' => 'Invalid payout amount after fee'], 400);
            }

            // Use database transaction to ensure atomicity
            DB::beginTransaction();

            try {
                // Deduct credits immediately when payout is requested
                $user->decrement('credits', $gross);

                // Create payout request
                $payout = Payout::create([
                    'user_id' => $user->id,
                    // amount is the NET amount sent to provider
                    'amount' => $net,
                    'gross_amount' => $gross,
                    'fee_amount' => $fee,
                    'net_amount' => $net,
                    'status' => 'pending',
                    'provider' => $request->input('provider', 'manual') ?: 'manual',
                    'idempotency_key' => (string) Str::uuid(),
                ]);

                // Create transaction record (pending status) with fee captured
                Transaction::create([
                    'user_id' => $user->id,
                    'type' => 'cashout',
                    'amount' => $gross,
                    'fee' => $fee,
                    'status' => 'pending',
                    'reference_id' => 'payout_' . $payout->id,
                ]);

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

            /** @var PayoutService $payoutService */
            $payoutService = app(PayoutService::class);
            $payoutService->markApprovedAndDispatch($payout, (int) Auth::id());

            $payout = $payout->fresh();
            
            // Only dispatch job for non-manual providers (PayPal, etc.)
            // Manual payouts are already marked as paid in markApprovedAndDispatch
            if ($payout->provider !== 'manual') {
                ExecutePayoutJob::dispatch($payout->id);
            }

            $message = $payout->provider === 'manual' 
                ? 'Payout approved and marked as paid' 
                : 'Payout approved';

            return response()->json([
                'message' => $message,
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

            /** @var PayoutService $payoutService */
            $payoutService = app(PayoutService::class);
            $payoutService->markRejected($payout, $request->admin_note);

            return response()->json([
                'message' => 'Payout rejected',
                'payout' => $payout->fresh()
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

            if (!in_array($payout->status, ['approved', 'processing'], true)) {
                return response()->json(['error' => 'Payout must be approved/processing first'], 400);
            }

            /** @var PayoutService $payoutService */
            $payoutService = app(PayoutService::class);
            $payoutService->markPaid($payout);

            return response()->json([
                'message' => 'Payout completed',
                'payout' => $payout->fresh()
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

            // Format payouts to ensure proper serialization
            $formattedPayouts = $payouts->map(function ($payout) {
                // Handle old payouts that might not have fee fields populated
                // If gross_amount is null, assume amount is the net amount and calculate backwards
                $grossAmount = $payout->gross_amount;
                $feeAmount = $payout->fee_amount ?? 0;
                $netAmount = $payout->net_amount ?? $payout->amount;
                
                // If gross_amount is missing, calculate it from net amount (assuming 20% fee)
                // This handles old payouts created before fee fields were added
                if ($grossAmount === null || $grossAmount === 0) {
                    // If we have net_amount or amount, calculate gross backwards
                    // net = gross * 0.8, so gross = net / 0.8
                    if ($netAmount > 0) {
                        $grossAmount = (int) ceil($netAmount / 0.8);
                        $feeAmount = $grossAmount - $netAmount;
                    } else {
                        // Fallback: use amount as gross if we can't calculate
                        $grossAmount = $payout->amount;
                        $feeAmount = 0;
                    }
                }
                
                // Ensure net_amount is set correctly
                if ($payout->net_amount === null) {
                    $netAmount = $grossAmount - $feeAmount;
                }
                
                return [
                    'id' => $payout->id,
                    'amount' => $payout->amount,
                    'gross_amount' => $grossAmount,
                    'fee_amount' => $feeAmount,
                    'net_amount' => $netAmount,
                    'status' => $payout->status,
                    'provider' => $payout->provider ?? 'manual',
                    'admin_note' => $payout->admin_note,
                    'created_at' => $payout->created_at ? $payout->created_at->toIso8601String() : null,
                    'updated_at' => $payout->updated_at ? $payout->updated_at->toIso8601String() : null,
                    'processed_at' => $payout->processed_at ? $payout->processed_at->toIso8601String() : null,
                ];
            });

            return response()->json([
                'payouts' => $formattedPayouts
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
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
