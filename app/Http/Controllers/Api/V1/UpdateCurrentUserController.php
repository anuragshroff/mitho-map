<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateCurrentUserRequest;
use App\Models\User;
use App\Services\Auth\PhoneVerificationCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UpdateCurrentUserController extends Controller
{
    public function __construct(public PhoneVerificationCodeService $verificationCodeService) {}

    /**
     * Handle the incoming request.
     *
     * @throws ValidationException
     */
    public function __invoke(UpdateCurrentUserRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        $nextPhone = null;

        if (isset($validated['phone']) && is_string($validated['phone']) && trim($validated['phone']) !== '') {
            $nextPhone = $this->verificationCodeService->normalizePhone($validated['phone']);
        }

        if ($nextPhone !== null && $nextPhone !== $user->phone) {
            $verificationCode = $validated['verification_code'] ?? null;

            if (! is_string($verificationCode) || $verificationCode === '') {
                throw ValidationException::withMessages([
                    'verification_code' => 'Phone verification code is required when changing phone number.',
                ]);
            }

            $isConsumed = $this->verificationCodeService->consumeVerifiedCode($nextPhone, $verificationCode);

            if (! $isConsumed) {
                throw ValidationException::withMessages([
                    'verification_code' => 'Phone verification is invalid or expired.',
                ]);
            }

            $user->phone = $nextPhone;
            $user->phone_verified_at = now();
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
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

    /**
     * Update the user's Expo Push Token for notifications.
     */
    public function updatePushToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $user->expo_push_token = $validated['token'];
        $user->save();

        return response()->json([
            'message' => 'Push token updated successfully.',
        ]);
    }
}
