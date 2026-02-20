<?php

use App\Models\PhoneVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;

function base64UrlEncodeForTest(string $input): string
{
    return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
}

/**
 * @return array{token: string, kid: string, n: string, e: string}
 */
function makeAppleIdentityTokenForTest(array $claims): array
{
    $keyResource = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);

    if ($keyResource === false) {
        throw new RuntimeException('Unable to generate RSA key pair for test.');
    }

    $details = openssl_pkey_get_details($keyResource);

    if (! is_array($details) || ! isset($details['rsa']) || ! is_array($details['rsa'])) {
        throw new RuntimeException('Unable to extract RSA key details for test.');
    }

    $rsa = $details['rsa'];
    $kid = 'apple-test-kid';
    $header = [
        'alg' => 'RS256',
        'kid' => $kid,
        'typ' => 'JWT',
    ];

    $payload = array_merge([
        'iss' => 'https://appleid.apple.com',
        'aud' => 'com.mithomap.ios',
        'exp' => now()->addMinutes(5)->timestamp,
        'sub' => 'apple-user-123',
        'email' => 'apple.user@example.com',
    ], $claims);

    $headerEncoded = base64UrlEncodeForTest(json_encode($header, JSON_THROW_ON_ERROR));
    $payloadEncoded = base64UrlEncodeForTest(json_encode($payload, JSON_THROW_ON_ERROR));
    $signingInput = $headerEncoded.'.'.$payloadEncoded;

    $signature = '';
    openssl_sign($signingInput, $signature, $keyResource, OPENSSL_ALGO_SHA256);

    $token = $signingInput.'.'.base64UrlEncodeForTest($signature);

    return [
        'token' => $token,
        'kid' => $kid,
        'n' => base64UrlEncodeForTest($rsa['n']),
        'e' => base64UrlEncodeForTest($rsa['e']),
    ];
}

it('sends a phone verification code', function () {
    $response = $this->postJson(route('api.v1.auth.phone.send-code'), [
        'phone' => '+9779812345678',
    ]);

    $response
        ->assertAccepted()
        ->assertJsonPath('phone', '+9779812345678');

    $this->assertDatabaseHas('phone_verification_codes', [
        'phone' => '+9779812345678',
    ]);
});

it('verifies phone verification code', function () {
    PhoneVerificationCode::query()->create([
        'phone' => '+9779812345679',
        'code_hash' => Hash::make('123456'),
        'attempts' => 0,
        'sent_at' => now(),
        'expires_at' => now()->addMinutes(5),
    ]);

    $this->postJson(route('api.v1.auth.phone.verify-code'), [
        'phone' => '+9779812345679',
        'code' => '123456',
    ])->assertSuccessful();

    expect(
        PhoneVerificationCode::query()
            ->where('phone', '+9779812345679')
            ->first()
            ?->verified_at
    )->not->toBeNull();
});

it('registers a user with phone verification and address', function () {
    PhoneVerificationCode::query()->create([
        'phone' => '+9779812345680',
        'code_hash' => Hash::make('222333'),
        'attempts' => 0,
        'sent_at' => now(),
        'expires_at' => now()->addMinutes(5),
        'verified_at' => now(),
    ]);

    $response = $this->postJson(route('api.v1.auth.register'), [
        'name' => 'Phone Verified User',
        'email' => 'phone.user@example.com',
        'phone' => '+9779812345680',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'verification_code' => '222333',
        'device_name' => 'iphone-16',
        'address' => [
            'label' => 'home',
            'line_1' => 'Baneshwor-10',
            'line_2' => null,
            'city' => 'Kathmandu',
            'state' => 'Bagmati',
            'postal_code' => '44600',
            'country' => 'NP',
            'latitude' => 27.7025,
            'longitude' => 85.3352,
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.email', 'phone.user@example.com')
        ->assertJsonPath('user.phone', '+9779812345680');

    $this->assertDatabaseHas('users', [
        'email' => 'phone.user@example.com',
        'phone' => '+9779812345680',
    ]);

    $this->assertDatabaseHas('user_addresses', [
        'line_1' => 'Baneshwor-10',
        'country' => 'NP',
        'is_default' => true,
    ]);
});

it('rejects registration when verification code is invalid', function () {
    PhoneVerificationCode::query()->create([
        'phone' => '+9779812345681',
        'code_hash' => Hash::make('999999'),
        'attempts' => 0,
        'sent_at' => now(),
        'expires_at' => now()->addMinutes(5),
        'verified_at' => now(),
    ]);

    $this->postJson(route('api.v1.auth.register'), [
        'name' => 'Invalid Code User',
        'email' => 'invalid.code@example.com',
        'phone' => '+9779812345681',
        'password' => 'SecurePass123!',
        'password_confirmation' => 'SecurePass123!',
        'verification_code' => '111111',
        'device_name' => 'android-16',
        'address' => [
            'line_1' => 'Lalitpur-12',
            'city' => 'Lalitpur',
            'state' => 'Bagmati',
            'postal_code' => '44700',
            'country' => 'NP',
        ],
    ])->assertUnprocessable();
});

it('creates user with social login and verified phone', function () {
    PhoneVerificationCode::query()->create([
        'phone' => '+9779812345682',
        'code_hash' => Hash::make('654321'),
        'attempts' => 0,
        'sent_at' => now(),
        'expires_at' => now()->addMinutes(5),
        'verified_at' => now(),
    ]);

    Http::fake([
        'https://oauth2.googleapis.com/tokeninfo*' => Http::response([
            'sub' => 'google-user-123',
            'email' => 'social.user@example.com',
            'name' => 'Social User',
        ], 200),
    ]);

    $response = $this->postJson(route('api.v1.auth.social.login'), [
        'provider' => 'google',
        'provider_token' => 'valid-google-id-token',
        'device_name' => 'iphone-social',
        'phone' => '+9779812345682',
        'verification_code' => '654321',
        'address' => [
            'line_1' => 'Pokhara-8',
            'city' => 'Pokhara',
            'state' => 'Gandaki',
            'postal_code' => '33700',
            'country' => 'NP',
        ],
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.email', 'social.user@example.com');

    $this->assertDatabaseHas('social_accounts', [
        'provider' => 'google',
        'provider_user_id' => 'google-user-123',
        'provider_email' => 'social.user@example.com',
    ]);
});

it('creates user with apple login and verified token signature', function () {
    config()->set('services.apple.client_ids', ['com.mithomap.ios']);

    PhoneVerificationCode::query()->create([
        'phone' => '+9779812345683',
        'code_hash' => Hash::make('777888'),
        'attempts' => 0,
        'sent_at' => now(),
        'expires_at' => now()->addMinutes(5),
        'verified_at' => now(),
    ]);

    $appleToken = makeAppleIdentityTokenForTest([]);

    Http::fake([
        'https://appleid.apple.com/auth/keys' => Http::response([
            'keys' => [[
                'kty' => 'RSA',
                'kid' => $appleToken['kid'],
                'use' => 'sig',
                'alg' => 'RS256',
                'n' => $appleToken['n'],
                'e' => $appleToken['e'],
            ]],
        ], 200),
    ]);

    $response = $this->postJson(route('api.v1.auth.social.login'), [
        'provider' => 'apple',
        'provider_token' => $appleToken['token'],
        'device_name' => 'iphone-apple-login',
        'phone' => '+9779812345683',
        'verification_code' => '777888',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('user.email', 'apple.user@example.com');

    $this->assertDatabaseHas('social_accounts', [
        'provider' => 'apple',
        'provider_user_id' => 'apple-user-123',
        'provider_email' => 'apple.user@example.com',
    ]);
});

it('returns authenticated user profile with auth me route', function () {
    $user = User::factory()->customer()->create();
    $user->addresses()->create([
        'label' => 'home',
        'line_1' => 'Thamel',
        'city' => 'Kathmandu',
        'state' => 'Bagmati',
        'postal_code' => '44600',
        'country' => 'NP',
        'is_default' => true,
    ]);

    Sanctum::actingAs($user, ['orders:read']);

    $this->getJson(route('api.v1.auth.me'))
        ->assertSuccessful()
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.phone', $user->phone)
        ->assertJsonPath('user.address.line_1', 'Thamel');
});
