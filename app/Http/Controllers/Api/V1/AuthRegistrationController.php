<?php

namespace App\Http\Controllers\Api\V1;

use App\Concerns\ResolvesApiTokenAbilities;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegisterUserRequest;
use App\Models\User;
use App\Services\Auth\PhoneVerificationCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthRegistrationController extends Controller
{
    use ResolvesApiTokenAbilities;

    public function __construct(public PhoneVerificationCodeService $verificationCodeService) {}

    /**
     * @throws ValidationException
     */
    public function store(RegisterUserRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $normalizedPhone = $this->verificationCodeService->normalizePhone($validated['phone']);

        $isConsumed = $this->verificationCodeService->consumeVerifiedCode(
            $normalizedPhone,
            $validated['verification_code'],
        );

        if (! $isConsumed) {
            throw ValidationException::withMessages([
                'verification_code' => 'Phone verification is required before registration.',
            ]);
        }

        /** @var array{user: User, token: string} $createdData */
        $createdData = DB::transaction(function () use ($validated, $normalizedPhone): array {
            $user = User::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $normalizedPhone,
                'password' => $validated['password'],
                'role' => UserRole::Customer,
                'phone_verified_at' => now(),
            ]);

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

        $createdData['user']->load('defaultAddress');

        return response()->json([
            'token_type' => 'Bearer',
            'token' => $createdData['token'],
            'user' => [
                'id' => $createdData['user']->id,
                'name' => $createdData['user']->name,
                'email' => $createdData['user']->email,
                'phone' => $createdData['user']->phone,
                'role' => $createdData['user']->role?->value,
                'address' => $createdData['user']->defaultAddress,
            ],
        ], 201);
    }
}
