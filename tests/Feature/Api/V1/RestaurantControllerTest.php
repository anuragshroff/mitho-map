<?php

use App\Models\Restaurant;

it('fetches all active restaurants', function () {
    Restaurant::factory()->count(3)->create(['is_active' => true]);
    Restaurant::factory()->create(['is_active' => false]);

    $response = $this->getJson(route('api.v1.restaurants.index'));

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

it('fetches a single active restaurant with loaded relationships', function () {
    $restaurant = Restaurant::factory()->create(['is_active' => true]);

    $response = $this->getJson(route('api.v1.restaurants.show', $restaurant));

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $restaurant->id);
});

it('returns 404 for an inactive restaurant', function () {
    $restaurant = Restaurant::factory()->create(['is_active' => false]);

    $response = $this->getJson(route('api.v1.restaurants.show', $restaurant));

    $response->assertStatus(404);
});
