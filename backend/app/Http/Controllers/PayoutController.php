<?php

namespace App\Http\Controllers;

use App\Models\Payout;
use App\Models\UserPayoutMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Exception;
use App\Jobs\ExecutePayoutJob;
use App\Services\PayoutService;

class PayoutController extends Controller
{
    /**
     * User requests payout
     */
    public function requestPayout(Request $request)
    {
        try {
            $request->validate([
                'amount' => 'required|integer|min:1',
                'payout_method_id' => 'required|integer',
            ]);

            $user = Auth::user();
            $method = UserPayoutMethod::where('user_id', $user->id)
                ->where('id', $request->input('payout_method_id'))
                ->firstOrFail();

            /** @var PayoutService $payoutService */
            $payoutService = app(PayoutService::class);
            $payout = $payoutService->requestPayout($user, (int) $request->amount, $method);

            return response()->json([
                'message' => 'Payout request submitted',
                'payout' => $payout
            ], 201);

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
            if ($payout->provider !== 'manual') {
                ExecutePayoutJob::dispatch($payout->id);
            }

            $message = 'Payout approved';

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
                    'method' => $payout->method,
                    'method_details' => $payout->method_details ?? null,
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
