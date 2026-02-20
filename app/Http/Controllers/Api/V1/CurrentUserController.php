<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CurrentUserController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->load(['defaultAddress', 'socialAccounts']);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role?->value,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'phone_verified_at' => $user->phone_verified_at?->toIso8601String(),
                'address' => $user->defaultAddress,
                'social_accounts' => $user->socialAccounts->map(fn ($account) => [
                    'provider' => $account->provider,
                    'provider_email' => $account->provider_email,
                ])->values(),
            ],
        ]);
    }
}
