<?php

namespace App\Http\Controllers;

use App\Services\DepositService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepositController extends Controller
{
    /**
     * Create a Whish collect URL for buying credits.
     * Returns a URL that the frontend can redirect the user to.
     */
    public function createWhishCollect(Request $request, DepositService $depositService)
    {
        $request->validate([
            'package' => 'nullable|string',
            'credits' => 'nullable|integer|min:1',
        ]);

        $user = Auth::user();

        $packages = (array) config('credit_packages.packages', []);
        $credits = (int) $request->input('credits', 0);

        if ($credits <= 0) {
            $key = (string) $request->input('package', '');
            if ($key === '' || !isset($packages[$key])) {
                return response()->json(['error' => 'Invalid package'], 422);
            }
            $credits = (int) ($packages[$key]['credits'] ?? 0);
        }

        $result = $depositService->createWhishCollect($user->id, $credits, [
            'callback_url' => (string) config('services.whish.webhook_url'),
            'return_url' => (string) config('services.whish.return_url'),
        ]);

        return response()->json([
            'collect_url' => $result['collect_url'],
            'reference' => $result['reference'],
            'transaction_id' => $result['transaction_id'],
        ]);
    }

    /**
     * Create a PayPal Checkout order for buying credits.
     * Returns approval_url for redirect.
     */
    public function createPayPalOrder(Request $request, DepositService $depositService)
    {
        $request->validate([
            'package' => 'nullable|string',
            'credits' => 'nullable|integer|min:1',
        ]);

        $user = Auth::user();

        $packages = (array) config('credit_packages.packages', []);
        $credits = (int) $request->input('credits', 0);

        if ($credits <= 0) {
            $key = (string) $request->input('package', '');
            if ($key === '' || !isset($packages[$key])) {
                return response()->json(['error' => 'Invalid package'], 422);
            }
            $credits = (int) ($packages[$key]['credits'] ?? 0);
        }

        $result = $depositService->createPayPalOrder($user->id, $credits, [
            'return_url' => (string) (config('services.paypal.checkout_return_url') ?: (rtrim((string) config('app.url'), '/') . '/credits/status')),
            'cancel_url' => (string) (config('services.paypal.checkout_cancel_url') ?: (rtrim((string) config('app.url'), '/') . '/credits')),
        ]);

        return response()->json([
            'approval_url' => $result['approval_url'],
            'reference' => $result['reference'],
            'transaction_id' => $result['transaction_id'],
            'order_id' => $result['order_id'],
        ]);
    }
}

