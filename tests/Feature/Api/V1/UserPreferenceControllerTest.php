<?php

use App\Models\User;
use App\Models\UserPreference;

it('returns empty object when user has no preferences', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->getJson(route('api.v1.user.preferences.show'));

    $response->assertStatus(200)
        ->assertJson(['data' => []]);
});

it('returns user preferences', function () {
    $user = User::factory()->create();
    $preference = UserPreference::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->getJson(route('api.v1.user.preferences.show'));

    $response->assertStatus(200)
        ->assertJsonPath('data.dietary_preferences', $preference->dietary_preferences)
        ->assertJsonPath('data.spice_level', $preference->spice_level);
});

it('can create user preferences if they do not exist', function () {
    $user = User::factory()->create();

    $data = [
        'dietary_preferences' => ['vegetarian', 'gluten_free'],
        'spice_level' => 'medium',
        'favorite_cuisines' => ['indian'],
    ];

    $response = $this->actingAs($user)->putJson(route('api.v1.user.preferences.update'), $data);

    $response->assertStatus(200)
        ->assertJsonPath('data.spice_level', 'medium');

    $this->assertDatabaseHas('user_preferences', [
        'user_id' => $user->id,
        'spice_level' => 'medium',
    ]);
});

it('can update existing user preferences', function () {
    $user = User::factory()->create();
    UserPreference::factory()->create([
        'user_id' => $user->id,
        'spice_level' => 'mild',
    ]);

    $data = [
        'dietary_preferences' => ['vegan'],
        'spice_level' => 'hot',
        'favorite_cuisines' => ['mexican'],
    ];

    $response = $this->actingAs($user)->putJson(route('api.v1.user.preferences.update'), $data);

    $response->assertStatus(200)
        ->assertJsonPath('data.spice_level', 'hot');

    $this->assertDatabaseHas('user_preferences', [
        'user_id' => $user->id,
        'spice_level' => 'hot',
    ]);
});
