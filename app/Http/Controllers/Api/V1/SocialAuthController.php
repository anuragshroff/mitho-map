<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\ResolvesApiTokenAbilities;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SocialLoginRequest;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Auth\PhoneVerificationCodeService;
use App\Services\Auth\SocialIdentityResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SocialAuthController extends Controller
{
    use ResolvesApiTokenAbilities;

    public function __construct(
        public SocialIdentityResolver $socialIdentityResolver,
        public PhoneVerificationCodeService $verificationCodeService,
    ) {}

    /**
     * @throws ValidationException
     */
    public function store(SocialLoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $identity = $this->socialIdentityResolver->resolve(
            $validated['provider'],
            $validated['provider_token'],
        );

        $normalizedPhone = isset($validated['phone'])
            ? $this->verificationCodeService->normalizePhone($validated['phone'])
            : null;

        /** @var array{user: User, token: string} $authData */
        $authData = DB::transaction(function () use ($validated, $identity, $normalizedPhone): array {
            $socialAccount = SocialAccount::query()
                ->with('user')
                ->where('provider', $identity['provider'])
                ->where('provider_user_id', $identity['provider_user_id'])
                ->first();

            $user = $socialAccount?->user;

            if ($user === null && is_string($identity['email']) && $identity['email'] !== '') {
                $user = User::query()->where('email', $identity['email'])->first();
            }

            if ($user === null) {
                if (! is_string($identity['email']) || trim($identity['email']) === '') {
                    throw ValidationException::withMessages([
                        'provider_token' => 'Social account must provide an email for first sign in.',
                    ]);
                }

                if ($normalizedPhone === null || ! isset($validated['verification_code'])) {
                    throw ValidationException::withMessages([
                        'phone' => 'Phone number and verification code are required for first social login.',
                    ]);
                }

                $isConsumed = $this->verificationCodeService->consumeVerifiedCode(
                    $normalizedPhone,
                    $validated['verification_code'],
                );

                if (! $isConsumed) {
                    throw ValidationException::withMessages([
                        'verification_code' => 'Phone verification is invalid or expired.',
                    ]);
                }

                $user = User::query()->create([
                    'name' => $validated['name'] ?? $identity['name'] ?? 'New User',
                    'email' => $identity['email'],
                    'phone' => $normalizedPhone,
                    'password' => bin2hex(random_bytes(24)),
                    'role' => UserRole::Customer,
                    'email_verified_at' => $identity['email'] === null ? null : now(),
                    'phone_verified_at' => now(),
                ]);
            } elseif (
                $user->phone === null
                && $normalizedPhone !== null
                && isset($validated['verification_code'])
            ) {
                $isConsumed = $this->verificationCodeService->consumeVerifiedCode(
                    $normalizedPhone,
                    $validated['verification_code'],
                );

                if ($isConsumed) {
                    $user->forceFill([
                        'phone' => $normalizedPhone,
                        'phone_verified_at' => now(),
                    ])->save();
                }
            }

            SocialAccount::query()->firstOrCreate(
                [
                    'provider' => $identity['provider'],
                    'provider_user_id' => $identity['provider_user_id'],
                ],
                [
                    'user_id' => $user->id,
                    'provider_email' => $identity['email'],
                    'provider_data' => $identity['data'],
                ],
            );

            if (
                isset($validated['address']['line_1'])
                && isset($validated['address']['city'])
                && isset($validated['address']['state'])
                && isset($validated['address']['postal_code'])
                && isset($validated['address']['country'])
                && ! $user->addresses()->where('is_default', true)->exists()
            ) {
                $address = $validated['address'];

                $user->addresses()->create([
                    'label' => $address['label'] ?? 'home',
                    'line_1' => $address['line_1'],
                    'line_2' => $address['line_2'] ?? null,
                    'city' => $address['city'],
                    'state' => $address['state'],
                    'postal_code' => $address['postal_code'],
                    'country' => strtoupper($address['country']),
                    'latitude' => $address['latitude'] ?? null,
                    'longitude' => $address['longitude'] ?? null,
                    'is_default' => true,
                ]);
            }

            $token = $user->createToken(
                $validated['device_name'],
                $this->resolveAbilitiesForUser($user),
                now()->addDays(30),
            )->plainTextToken;

            return [
                'user' => $user,
                'token' => $token,
            ];
        });

        $authData['user']->load(['defaultAddress', 'socialAccounts']);

        return response()->json([
            'token_type' => 'Bearer',
            'token' => $authData['token'],
            'user' => [
                'id' => $authData['user']->id,
                'name' => $authData['user']->name,
                'email' => $authData['user']->email,
                'phone' => $authData['user']->phone,
                'role' => $authData['user']->role?->value,
                'address' => $authData['user']->defaultAddress,
            ],
        ]);
    }
}
