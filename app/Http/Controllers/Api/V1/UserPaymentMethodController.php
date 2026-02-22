<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreUserPaymentMethodRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserPaymentMethodController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $methods = $user->paymentMethods()
            ->latest('id')
            ->get();

        return response()->json([
            'data' => $methods,
        ]);
    }

    public function store(StoreUserPaymentMethodRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $hasExistingMethods = $user->paymentMethods()->exists();
        $isDefault = (bool) ($validated['is_default'] ?? false);

        if (! $hasExistingMethods) {
            $isDefault = true;
        }

        if ($isDefault) {
            $user->paymentMethods()->update(['is_default' => false]);
        }

        $method = $user->paymentMethods()->create([
            'provider' => $validated['provider'],
            'brand' => $validated['brand'] ?? null,
            'last_four' => $validated['last_four'],
            'exp_month' => $validated['exp_month'] ?? null,
            'exp_year' => $validated['exp_year'] ?? null,
            'token_reference' => $validated['token_reference'] ?? null,
            'is_default' => $isDefault,
            'metadata' => $validated['metadata'] ?? null,
        ]);

        return response()->json([
            'data' => $method,
            'message' => 'Payment method added successfully.',
        ], 201);
    }
}
