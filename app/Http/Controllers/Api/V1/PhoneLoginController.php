<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\ResolvesApiTokenAbilities;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PhoneLoginRequest;
use App\Models\User;
use App\Services\Auth\PhoneVerificationCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PhoneLoginController extends Controller
{
    use ResolvesApiTokenAbilities;

    public function __construct(public PhoneVerificationCodeService $verificationCodeService) {}

    /**
     * @throws ValidationException
     */
    public function store(PhoneLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $normalizedPhone = $this->verificationCodeService->normalizePhone($validated['phone']);

        $isVerified = $this->verificationCodeService->verifyCode(
            $normalizedPhone,
            $validated['verification_code'],
        );

        if (! $isVerified) {
            throw ValidationException::withMessages([
                'verification_code' => 'The provided verification code is invalid or expired.',
            ]);
        }

        $isConsumed = $this->verificationCodeService->consumeVerifiedCode(
            $normalizedPhone,
            $validated['verification_code'],
        );

        if (! $isConsumed) {
            throw ValidationException::withMessages([
                'verification_code' => 'The verification code has already been used.',
            ]);
        }

        $user = User::query()->where('phone', $normalizedPhone)->first();
        $isNewUser = false;

        if ($user === null) {
            $user = $this->createPhoneOnlyUser($normalizedPhone);
            $isNewUser = true;
        } elseif ($user->phone_verified_at === null) {
            $user->forceFill([
                'phone_verified_at' => now(),
            ])->save();
        }

        $abilities = $this->resolveAbilitiesForUser($user);
        $newAccessToken = $user->createToken(
            $validated['device_name'],
            $abilities,
            now()->addDays(30),
        );

        $user->load('defaultAddress');

        return response()->json([
            'token_type' => 'Bearer',
            'token' => $newAccessToken->plainTextToken,
            'expires_at' => $newAccessToken->accessToken->expires_at?->toIso8601String(),
            'abilities' => $abilities,
            'is_new_user' => $isNewUser,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role?->value,
                'address' => $user->defaultAddress,
            ],
        ]);
    }

    protected function createPhoneOnlyUser(string $normalizedPhone): User
    {
        $digits = preg_replace('/\D+/', '', $normalizedPhone);
        $base = is_string($digits) && $digits !== '' ? 'user'.$digits : 'user'.now()->getTimestamp();
        $displayDigits = is_string($digits) && $digits !== '' ? substr($digits, -4) : '0000';

        return User::query()->create([
            'name' => 'Customer '.$displayDigits,
            'email' => $this->resolvePlaceholderEmail($base),
            'phone' => $normalizedPhone,
            'password' => Str::random(40),
            'role' => UserRole::Customer,
            'phone_verified_at' => now(),
        ]);
    }

    protected function resolvePlaceholderEmail(string $base): string
    {
        $suffix = 0;

        while (true) {
            $email = $suffix === 0
                ? sprintf('%s@phone.mithomap.local', $base)
                : sprintf('%s_%d@phone.mithomap.local', $base, $suffix);

            $exists = User::query()
                ->where('email', $email)
                ->exists();

            if (! $exists) {
                return $email;
            }

            $suffix++;
        }
    }
}
