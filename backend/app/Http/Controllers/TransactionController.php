<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;

class TransactionController extends Controller
{
    /**
     * Create a transaction (e.g., credit purchase)
     */
    public function createTransaction(Request $request)
    {
        try {
            $request->validate([
                'type' => 'required|string|in:credit_purchase,skill_payment,skill_earning,cashout,refund',
                'amount' => 'required|integer|min:1',
                'reference_id' => 'nullable|string'
            ]);

            $user = Auth::user();

            // Use database transaction to ensure atomicity
            DB::beginTransaction();

            try {
                // For credit purchases, add credits to user
                if ($request->type === 'credit_purchase') {
                    $user->increment('credits', $request->amount);
                }

                $transaction = Transaction::create([
                    'user_id' => $user->id,
                    'type' => $request->type,
                    'amount' => $request->amount,
                    'fee' => 0,
                    'status' => 'completed',
                    'reference_id' => $request->reference_id
                ]);

                DB::commit();

                return response()->json([
                    'message' => 'Transaction created successfully',
                    'transaction' => $transaction
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
     * Get transactions of authenticated user with balance and stats
     */
    public function getUserTransactions()
    {
        try {
            $user = Auth::user();
            $userId = $user->id;
            
            // Get all transactions
            $transactions = Transaction::where('user_id', $userId)
                ->latest()
                ->get();

            // Calculate current balance from user's credits
            $currentBalance = $user->credits ?? 0;

            // Calculate pending cashout (sum of pending cashout transactions)
            $pendingCashout = Transaction::where('user_id', $userId)
                ->where('type', 'cashout')
                ->where('status', 'pending')
                ->sum('amount');

            // Calculate taught this month (skill_earning transactions this month)
            $startOfMonth = now()->startOfMonth();
            $taughtThisMonth = Transaction::where('user_id', $userId)
                ->where('type', 'skill_earning')
                ->where('status', 'completed')
                ->where('created_at', '>=', $startOfMonth)
                ->sum('amount');

            // Calculate learned this month (skill_payment transactions this month)
            $learnedThisMonth = Transaction::where('user_id', $userId)
                ->where('type', 'skill_payment')
                ->where('status', 'completed')
                ->where('created_at', '>=', $startOfMonth)
                ->sum('amount');

            // Calculate running balance for each transaction
            // Start from oldest and work forward to calculate balance after each transaction
            $orderedTransactions = $transactions->sortBy('created_at');
            $runningBalance = 0;
            
            // Calculate balance forward from oldest transaction
            foreach ($orderedTransactions as $transaction) {
                // Adjust balance based on transaction type
                if ($transaction->type === 'skill_earning' || $transaction->type === 'credit_purchase') {
                    $runningBalance += $transaction->amount; // Add credits
                } elseif ($transaction->type === 'skill_payment' || $transaction->type === 'cashout') {
                    // Cashouts are deducted when requested (pending or completed)
                    $runningBalance -= $transaction->amount; // Subtract credits
                }
                
                // Store the balance AFTER this transaction
                $transaction->balance = $runningBalance;
            }
            
            // Return in reverse chronological order (newest first) with balance
            $transactionsWithBalance = $transactions->sortByDesc('created_at')->values();

            return response()->json([
                'transactions' => $transactionsWithBalance,
                'balance' => $currentBalance,
                'pending_cashout' => $pendingCashout,
                'taught_this_month' => $taughtThisMonth,
                'learned_this_month' => $learnedThisMonth
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Update transaction status (admin/internal)
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            // Ensure user is admin (defense in depth)
            if (!Auth::user()->is_admin) {
                return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
            }

            $request->validate([
                'status' => 'required|in:pending,completed,failed'
            ]);

            $transaction = Transaction::findOrFail($id);
            $transaction->update(['status' => $request->status]);

            return response()->json([
                'message' => 'Transaction status updated',
                'transaction' => $transaction
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    /**
     * Admin: Get all transactions
     */
    public function getAllTransactions()
    {
        try {
            // Ensure user is admin (defense in depth)
            if (!Auth::user()->is_admin) {
                return response()->json(['error' => 'Unauthorized. Admin access required.'], 403);
            }

            $transactions = Transaction::with('user:id,name,email')
                ->latest()
                ->get();

            return response()->json([
                'transactions' => $transactions
            ], 200);

        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
}
