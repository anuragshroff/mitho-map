<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('issues an api token for valid credentials', function () {
    $user = User::factory()->customer()->create([
        'password' => Hash::make('SecurePass123!'),
    ]);

    $response = $this->postJson(route('api.v1.auth.token.store'), [
        'email' => $user->email,
        'password' => 'SecurePass123!',
        'device_name' => 'iphone-15-pro',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.id', $user->id);

    $token = $response->json('token');

    expect($token)->not->toBeNull();

    $this->assertDatabaseCount('personal_access_tokens', 1);

    $this->getJson(route('api.v1.orders.index'), [
        'Authorization' => 'Bearer '.$token,
    ])->assertSuccessful();
});

it('rejects token request with invalid credentials', function () {
    $user = User::factory()->customer()->create([
        'password' => Hash::make('SecurePass123!'),
    ]);

    $this->postJson(route('api.v1.auth.token.store'), [
        'email' => $user->email,
        'password' => 'incorrect-password',
        'device_name' => 'pixel-8',
    ])->assertUnprocessable();

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('forbids access when token does not have required ability', function () {
    $driver = User::factory()->driver()->create();
    $token = $driver->createToken('driver-device', ['tracking:write'])->plainTextToken;

    $this->getJson(route('api.v1.orders.index'), [
        'Authorization' => 'Bearer '.$token,
    ])->assertForbidden();
});

it('revokes current token on logout endpoint', function () {
    $user = User::factory()->customer()->create();
    $token = $user->createToken('android-phone', ['orders:read'])->plainTextToken;

    $this->deleteJson(route('api.v1.auth.token.destroy'), [], [
        'Authorization' => 'Bearer '.$token,
    ])->assertNoContent();

    $this->assertDatabaseCount('personal_access_tokens', 0);
});
