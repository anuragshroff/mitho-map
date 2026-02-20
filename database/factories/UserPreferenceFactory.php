<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserPreference>
 */
class UserPreferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'dietary_preferences' => fake()->randomElements(['vegetarian', 'vegan', 'gluten_free', 'halal'], 2),
            'spice_level' => fake()->randomElement(['mild', 'medium', 'hot', 'extra_hot']),
            'favorite_cuisines' => fake()->randomElements(['indian', 'chinese', 'italian', 'mexican'], 2),
        ];
    }
}
