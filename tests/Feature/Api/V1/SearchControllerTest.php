<?php

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;

it('returns restaurant category and menu item matches for a catalog query', function () {
    $pizzaCategory = Category::factory()->create([
        'name' => 'Pizza',
        'slug' => 'pizza',
        'is_active' => true,
    ]);

    $matchingRestaurant = Restaurant::factory()->create([
        'name' => 'Mario Pizza House',
        'is_active' => true,
    ]);
    $matchingRestaurant->categories()->attach($pizzaCategory);

    MenuItem::factory()->for($matchingRestaurant)->create([
        'name' => 'Pepperoni Pizza',
        'description' => 'Classic thin crust pizza.',
        'is_available' => true,
    ]);

    $nonMatchingRestaurant = Restaurant::factory()->create([
        'name' => 'Sakura Sushi Place',
        'is_active' => true,
    ]);

    MenuItem::factory()->for($nonMatchingRestaurant)->create([
        'name' => 'Salmon Roll',
        'description' => 'Fresh sushi roll.',
        'is_available' => true,
    ]);

    $response = $this->getJson(route('api.v1.search.index', [
        'q' => '  pizza  ',
        'limit' => 5,
    ]));

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.query', 'pizza')
        ->assertJsonCount(1, 'data.restaurants')
        ->assertJsonCount(1, 'data.categories')
        ->assertJsonCount(1, 'data.menu_items')
        ->assertJsonPath('data.restaurants.0.id', $matchingRestaurant->id)
        ->assertJsonPath('data.categories.0.id', $pizzaCategory->id);
});

it('filters out inactive restaurants and unavailable menu items from search results', function () {
    $pizzaCategory = Category::factory()->create([
        'name' => 'Pizza',
        'slug' => 'pizza',
        'is_active' => true,
    ]);

    $activeRestaurant = Restaurant::factory()->create([
        'name' => 'Pizza Plaza',
        'is_active' => true,
    ]);
    $activeRestaurant->categories()->attach($pizzaCategory);

    $inactiveRestaurant = Restaurant::factory()->create([
        'name' => 'Old Pizza Corner',
        'is_active' => false,
    ]);
    $inactiveRestaurant->categories()->attach($pizzaCategory);

    $activeMenuItem = MenuItem::factory()->for($activeRestaurant)->create([
        'name' => 'Veggie Pizza',
        'is_available' => true,
    ]);

    MenuItem::factory()->for($activeRestaurant)->create([
        'name' => 'Cheese Pizza',
        'is_available' => false,
    ]);

    $inactiveRestaurantMenuItem = MenuItem::factory()->for($inactiveRestaurant)->create([
        'name' => 'Margherita Pizza',
        'is_available' => true,
    ]);

    $response = $this->getJson(route('api.v1.search.index', ['q' => 'pizza']));

    $response
        ->assertSuccessful()
        ->assertJsonCount(1, 'data.restaurants')
        ->assertJsonCount(1, 'data.menu_items')
        ->assertJsonPath('data.restaurants.0.id', $activeRestaurant->id)
        ->assertJsonPath('data.menu_items.0.id', $activeMenuItem->id);

    $restaurantIds = $response->collect('data.restaurants')->pluck('id')->all();
    $menuItemIds = $response->collect('data.menu_items')->pluck('id')->all();

    expect($restaurantIds)->not->toContain($inactiveRestaurant->id);
    expect($menuItemIds)->not->toContain($inactiveRestaurantMenuItem->id);
});

it('validates minimum query length for catalog search', function () {
    $response = $this->getJson(route('api.v1.search.index', ['q' => 'a']));

    $response
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['q']);
});
