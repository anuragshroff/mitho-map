<?php

use App\Models\User;
use Illuminate\Support\Facades\Route;

function bearerHeadersForUserAliasTest(User $user): array
{
    $token = $user->createToken('user-app-alias-test', ['*'])->plainTextToken;

    return [
        'Authorization' => 'Bearer '.$token,
        'Accept' => 'application/json',
    ];
}

it('supports sign-in alias endpoint', function () {
    $user = User::factory()->create();

    $response = $this->postJson('/api/v1/auth/sign-in', [
        'email' => $user->email,
        'password' => 'password',
        'device_name' => 'user-app',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('user.id', $user->id);
});

it('supports users me alias endpoints', function () {
    $user = User::factory()->create([
        'name' => 'Alias User',
        'email' => 'alias.user@example.com',
    ]);
    $headers = bearerHeadersForUserAliasTest($user);

    $this->withHeaders($headers)
        ->getJson('/api/v1/users/me')
        ->assertSuccessful()
        ->assertJsonPath('user.id', $user->id);

    $this->withHeaders($headers)
        ->patchJson('/api/v1/users/me', [
            'name' => 'Alias User Updated',
            'email' => 'alias.user.updated@example.com',
        ])
        ->assertSuccessful()
        ->assertJsonPath('user.name', 'Alias User Updated')
        ->assertJsonPath('user.email', 'alias.user.updated@example.com');
});

it('supports users me addresses alias endpoints', function () {
    $user = User::factory()->create();
    $headers = bearerHeadersForUserAliasTest($user);

    $this->withHeaders($headers)
        ->postJson('/api/v1/users/me/addresses', [
            'label' => 'Home',
            'line_1' => '123 Alias Street',
            'city' => 'Kathmandu',
            'state' => 'Bagmati',
            'postal_code' => '44600',
            'country' => 'NP',
            'latitude' => 27.7172,
            'longitude' => 85.3240,
            'is_default' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.label', 'Home');

    $this->withHeaders($headers)
        ->getJson('/api/v1/users/me/addresses')
        ->assertSuccessful()
        ->assertJsonCount(1, 'data');
});

it('supports deals alias endpoint', function () {
    $this->getJson('/api/v1/deals')
        ->assertSuccessful()
        ->assertJsonStructure(['data']);
});

it('registers tracking and chat alias routes', function () {
    expect(Route::has('api.v1.orders.tracking.show'))->toBeTrue();
    expect(Route::has('api.v1.chat.messages.index'))->toBeTrue();
    expect(Route::has('api.v1.chat.messages.store'))->toBeTrue();
});
