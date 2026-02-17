<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Story>
 */
class StoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'created_by' => User::factory()->restaurant(),
            'media_url' => fake()->imageUrl(1080, 1920),
            'caption' => fake()->boolean(60) ? fake()->sentence() : null,
            'expires_at' => now()->addDay(),
            'is_active' => true,
        ];
    }
}
