<?php

namespace App\Http\Controllers;

use App\Models\UserPayoutMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Exception;

class PayoutMethodController extends Controller
{
    public function index()
    {
        try {
            $methods = Auth::user()
                ->payoutMethods()
                ->orderByDesc('is_default')
                ->latest()
                ->get()
                ->map(function (UserPayoutMethod $method) {
                    return [
                        'id' => $method->id,
                        'provider' => $method->provider,
                        'method' => $method->method,
                        'is_default' => (bool) $method->is_default,
                        'is_verified' => (bool) $method->is_verified,
                        'details' => $method->getSafeDetails(),
                        'created_at' => $method->created_at?->toIso8601String(),
                    ];
                });

            return response()->json(['methods' => $methods], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'provider' => ['required', 'string', Rule::in(config('payments.payout_providers', []))],
                'method' => ['required', 'string', Rule::in(config('payments.payout_methods', []))],
                'label' => 'required|string|max:80',
                'last4' => 'nullable|string|regex:/^[0-9]{4}$/',
                'provider_reference' => 'required|string|max:140',
                'is_default' => 'sometimes|boolean',
            ]);

            $user = Auth::user();
            $makeDefault = (bool) $request->input('is_default', false);

            if ($makeDefault) {
                UserPayoutMethod::where('user_id', $user->id)->update(['is_default' => false]);
            }

            $method = new UserPayoutMethod();
            $method->user_id = $user->id;
            $method->provider = $request->input('provider');
            $method->method = $request->input('method');
            $method->is_default = $makeDefault || !UserPayoutMethod::where('user_id', $user->id)->exists();
            $method->is_verified = false;
            $method->details = [
                'label' => $request->input('label'),
                'last4' => $request->input('last4'),
                'provider_reference' => $request->input('provider_reference'),
            ];
            $method->save();

            return response()->json([
                'message' => 'Payout method saved',
                'method' => [
                    'id' => $method->id,
                    'provider' => $method->provider,
                    'method' => $method->method,
                    'is_default' => (bool) $method->is_default,
                    'is_verified' => (bool) $method->is_verified,
                    'details' => $method->getSafeDetails(),
                    'created_at' => $method->created_at?->toIso8601String(),
                ],
            ], 201);
        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    public function setDefault($id)
    {
        try {
            $user = Auth::user();
            $method = UserPayoutMethod::where('user_id', $user->id)->findOrFail($id);

            UserPayoutMethod::where('user_id', $user->id)->update(['is_default' => false]);
            $method->is_default = true;
            $method->save();

            return response()->json(['message' => 'Default payout method updated'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $user = Auth::user();
            $method = UserPayoutMethod::where('user_id', $user->id)->findOrFail($id);
            $method->delete();

            return response()->json(['message' => 'Payout method deleted'], 200);
        } catch (Exception $e) {
            return response()->json(['error' => 'Something went wrong'], 500);
        }
    }
}
