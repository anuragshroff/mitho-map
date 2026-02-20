<?php

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use App\Models\SpecialOffer;
use App\Models\User;
use App\Models\UserPreference;
use Laravel\Sanctum\Sanctum;

it('returns preference-aware ai food suggestions for authenticated user', function () {
    $user = User::factory()->customer()->create();

    UserPreference::factory()->create([
        'user_id' => $user->id,
        'favorite_cuisines' => ['indian'],
        'dietary_preferences' => ['vegetarian'],
        'spice_level' => 'medium',
    ]);

    $restaurant = Restaurant::factory()->create([
        'is_open' => true,
        'is_active' => true,
    ]);

    $category = Category::factory()->create([
        'name' => 'Indian',
        'slug' => 'indian',
        'is_active' => true,
    ]);

    $restaurant->categories()->attach($category->id);

    $menuItem = MenuItem::factory()->create([
        'restaurant_id' => $restaurant->id,
        'name' => 'Paneer Tikka Bowl',
        'description' => 'Vegetarian indian bowl',
        'is_available' => true,
    ]);

    SpecialOffer::factory()->create([
        'restaurant_id' => $restaurant->id,
        'is_active' => true,
        'valid_from' => now()->subHour(),
        'valid_until' => now()->addHour(),
    ]);

    Sanctum::actingAs($user, ['orders:read']);

    $response = $this->getJson(route('api.v1.ai.food-suggestions.index'));

    $response
        ->assertSuccessful()
        ->assertJsonPath('signals.favorite_cuisines.0', 'indian')
        ->assertJsonPath('data.0.menu_item_id', $menuItem->id)
        ->assertJsonPath('data.0.offer_boost', true)
        ->assertJsonPath('data.0.reason', 'preference_match');
});

it('requires authentication for ai food suggestions', function () {
    $this->getJson(route('api.v1.ai.food-suggestions.index'))
        ->assertUnauthorized();
});
