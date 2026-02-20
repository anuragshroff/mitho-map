<?php

use App\Models\Category;

it('fetches all active categories with restaurant count', function () {
    Category::factory()->count(3)->create(['is_active' => true]);
    Category::factory()->create(['is_active' => false]);

    $response = $this->getJson(route('api.v1.categories.index'));

    $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
});

it('fetches a single active category', function () {
    $category = Category::factory()->create(['is_active' => true]);

    $response = $this->getJson(route('api.v1.categories.show', $category));

    $response->assertStatus(200)
        ->assertJsonPath('data.id', $category->id);
});

it('returns 404 for an inactive category', function () {
    $category = Category::factory()->create(['is_active' => false]);

    $response = $this->getJson(route('api.v1.categories.show', $category));

    $response->assertStatus(404);
});
