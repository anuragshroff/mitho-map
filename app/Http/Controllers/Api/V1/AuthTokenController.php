<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\ResolvesApiTokenAbilities;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreApiTokenRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthTokenController extends Controller
{
    use ResolvesApiTokenAbilities;

    /**
     * @throws ValidationException
     */
    public function store(StoreApiTokenRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::query()->where('email', $validated['email'])->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials are incorrect.',
            ]);
        }

        $abilities = $this->resolveAbilitiesForUser($user);
        $newAccessToken = $user->createToken(
            $validated['device_name'],
            $abilities,
            now()->addDays(30),
        );

        return response()->json([
            'token_type' => 'Bearer',
            'token' => $newAccessToken->plainTextToken,
            'expires_at' => $newAccessToken->accessToken->expires_at?->toIso8601String(),
            'abilities' => $abilities,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role?->value,
            ],
        ]);
    }

    public function destroy(Request $request): Response
    {
        $user = $request->user();
        $currentToken = $user?->currentAccessToken();

        if ($currentToken !== null) {
            $currentToken->delete();
        }

        return response()->noContent();
    }
}
