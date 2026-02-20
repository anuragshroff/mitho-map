<?php

use App\Models\User;
use App\Models\UserAddress;

it('can fetch user addresses', function () {
    $user = User::factory()->create();
    UserAddress::factory()->count(3)->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson(route('api.v1.addresses.index'));

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

it('can store a new address and set to default', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->postJson(route('api.v1.addresses.store'), [
        'label' => 'Home',
        'line_1' => '123 Test St',
        'city' => 'Kathmandu',
        'state' => 'Bagmati',
        'postal_code' => '44600',
        'country' => 'NP',
        'latitude' => 27.7172,
        'longitude' => 85.3240,
        'is_default' => true,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('data.label', 'Home');

    $this->assertDatabaseHas('user_addresses', [
        'user_id' => $user->id,
        'label' => 'Home',
        'is_default' => 1,
    ]);
});

it('unsets other default addresses when storing a new default', function () {
    $user = User::factory()->create();
    $oldAddress = UserAddress::factory()->create(['user_id' => $user->id, 'is_default' => true]);

    $this->actingAs($user)->postJson(route('api.v1.addresses.store'), [
        'label' => 'Work',
        'line_1' => '456 Test Ave',
        'city' => 'Kathmandu',
        'state' => 'Bagmati',
        'postal_code' => '44600',
        'country' => 'NP',
        'latitude' => 27.7172,
        'longitude' => 85.3240,
        'is_default' => true,
    ]);

    expect($oldAddress->fresh()->is_default)->toBeFalse();
});

it('can update an address', function () {
    $user = User::factory()->create();
    $address = UserAddress::factory()->create(['user_id' => $user->id, 'label' => 'Old Label']);

    $response = $this->actingAs($user)->putJson(route('api.v1.addresses.update', $address), [
        'label' => 'New Label',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.label', 'New Label');
});

it('cannot update another users address', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $address = UserAddress::factory()->create(['user_id' => $user2->id]);

    $response = $this->actingAs($user1)->putJson(route('api.v1.addresses.update', $address), [
        'label' => 'Hacked',
    ]);

    $response->assertStatus(403);
});

it('can delete an address', function () {
    $user = User::factory()->create();
    $address = UserAddress::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->deleteJson(route('api.v1.addresses.destroy', $address));

    $response->assertStatus(200);
    $this->assertDatabaseMissing('user_addresses', ['id' => $address->id]);
});
