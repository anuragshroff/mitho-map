<?php

use App\Models\User;

beforeEach(function () {
    config()->set('broadcasting.default', 'reverb');
    config()->set('broadcasting.connections.reverb.key', 'test-key');
    config()->set('broadcasting.connections.reverb.secret', 'test-secret');
    config()->set('broadcasting.connections.reverb.app_id', 'test-app');
});

it('forbids users from subscribing to another user private channel', function () {
    $driver = User::factory()->driver()->create();
    $token = $driver->createToken('driver-mobile', ['orders:read', 'orders:write', 'tracking:write'])->plainTextToken;
    $otherDriver = User::factory()->driver()->create();

    $this->withHeaders([
        'Authorization' => 'Bearer '.$token,
    ])->postJson('/api/v1/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => 'private-App.Models.User.'.$otherDriver->id,
    ])->assertForbidden();
});

it('requires sanctum authentication for broadcast channel auth', function () {
    $driver = User::factory()->driver()->create();

    $this->postJson('/api/v1/broadcasting/auth', [
        'socket_id' => '1234.5678',
        'channel_name' => 'private-App.Models.User.'.$driver->id,
    ])->assertUnauthorized();
});
