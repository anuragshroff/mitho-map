<?php

use App\Models\PhoneVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('signs in an existing user with a whatsapp verification code', function () {
    $user = User::factory()->customer()->create([
        'phone' => '+9779812345700',
        'phone_verified_at' => null,
    ]);

    PhoneVerificationCode::query()->create([
        'phone' => '+9779812345700',
        'code_hash' => Hash::make('123456'),
        'attempts' => 0,
        'sent_at' => now(),
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->postJson(route('api.v1.auth.phone.login'), [
        'phone' => '+9779812345700',
        'verification_code' => '123456',
        'device_name' => 'iphone-17',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('is_new_user', false)
        ->assertJsonPath('user.id', $user->id)
        ->assertJsonPath('user.phone', '+9779812345700');

    $user->refresh();
    $verificationCode = PhoneVerificationCode::query()
        ->where('phone', '+9779812345700')
        ->first();

    expect($user->phone_verified_at)->not->toBeNull()
        ->and($verificationCode?->verified_at)->not->toBeNull()
        ->and($verificationCode?->consumed_at)->not->toBeNull();
});

it('creates a customer account on first phone login', function () {
    PhoneVerificationCode::query()->create([
        'phone' => '+9779812345711',
        'code_hash' => Hash::make('654321'),
        'attempts' => 0,
        'sent_at' => now(),
        'expires_at' => now()->addMinutes(10),
    ]);

    $response = $this->postJson(route('api.v1.auth.phone.login'), [
        'phone' => '+9779812345711',
        'verification_code' => '654321',
        'device_name' => 'android-17',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('is_new_user', true)
        ->assertJsonPath('user.phone', '+9779812345711')
        ->assertJsonPath('user.role', 'customer');

    $user = User::query()->where('phone', '+9779812345711')->first();

    expect($user)->not->toBeNull()
        ->and($user?->email)->toContain('@phone.mithomap.local')
        ->and($user?->phone_verified_at)->not->toBeNull();
});

it('rejects phone login when verification code is invalid', function () {
    PhoneVerificationCode::query()->create([
        'phone' => '+9779812345722',
        'code_hash' => Hash::make('112233'),
        'attempts' => 0,
        'sent_at' => now(),
        'expires_at' => now()->addMinutes(10),
    ]);

    $this->postJson(route('api.v1.auth.phone.login'), [
        'phone' => '+9779812345722',
        'verification_code' => '000000',
        'device_name' => 'android-17',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['verification_code']);
});
