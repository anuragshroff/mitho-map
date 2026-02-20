<?php

use App\Models\PhoneVerificationCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

it('updates authenticated user profile', function () {
    $user = User::factory()->customer()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
        'email_verified_at' => now(),
    ]);

    Sanctum::actingAs($user, ['orders:read']);

    $this->putJson(route('api.v1.auth.me.update'), [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ])
        ->assertOk()
        ->assertJsonPath('user.name', 'Updated Name')
        ->assertJsonPath('user.email', 'updated@example.com');

    $user->refresh();

    expect($user->name)->toBe('Updated Name')
        ->and($user->email)->toBe('updated@example.com')
        ->and($user->email_verified_at)->toBeNull();
});

it('keeps email verification timestamp when email does not change', function () {
    $verifiedAt = now()->subDay();

    $user = User::factory()->customer()->create([
        'name' => 'Customer',
        'email' => 'same@example.com',
        'email_verified_at' => $verifiedAt,
    ]);

    Sanctum::actingAs($user, ['orders:read']);

    $this->putJson(route('api.v1.auth.me.update'), [
        'name' => 'Customer Updated',
        'email' => 'same@example.com',
    ])
        ->assertOk()
        ->assertJsonPath('user.name', 'Customer Updated')
        ->assertJsonPath('user.email', 'same@example.com');

    $user->refresh();

    expect($user->email_verified_at)->not->toBeNull();
});

it('validates unique email when updating profile', function () {
    $user = User::factory()->customer()->create([
        'email' => 'customer@example.com',
    ]);
    $other = User::factory()->customer()->create([
        'email' => 'other@example.com',
    ]);

    Sanctum::actingAs($user, ['orders:read']);

    $this->putJson(route('api.v1.auth.me.update'), [
        'name' => 'Customer',
        'email' => $other->email,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
});

it('updates phone number when verification code is provided', function () {
    $user = User::factory()->customer()->create([
        'phone' => '+9779812345600',
    ]);

    PhoneVerificationCode::query()->create([
        'phone' => '+9779812345699',
        'code_hash' => Hash::make('123456'),
        'attempts' => 0,
        'sent_at' => now(),
        'expires_at' => now()->addMinutes(5),
        'verified_at' => now(),
    ]);

    Sanctum::actingAs($user, ['orders:read']);

    $this->putJson(route('api.v1.auth.me.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'phone' => '+9779812345699',
        'verification_code' => '123456',
    ])
        ->assertOk()
        ->assertJsonPath('user.phone', '+9779812345699');

    $user->refresh();

    expect($user->phone)->toBe('+9779812345699')
        ->and($user->phone_verified_at)->not->toBeNull();
});

it('rejects phone update when verification code is invalid', function () {
    $user = User::factory()->customer()->create([
        'phone' => '+9779812345601',
    ]);

    PhoneVerificationCode::query()->create([
        'phone' => '+9779812345688',
        'code_hash' => Hash::make('654321'),
        'attempts' => 0,
        'sent_at' => now(),
        'expires_at' => now()->addMinutes(5),
        'verified_at' => now(),
    ]);

    Sanctum::actingAs($user, ['orders:read']);

    $this->putJson(route('api.v1.auth.me.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'phone' => '+9779812345688',
        'verification_code' => '000000',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['verification_code']);
});
