<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Banner>
 */
class BannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'image_url' => fake()->imageUrl(),
            'target_url' => fake()->url(),
            'is_active' => fake()->boolean(),
            'order' => fake()->numberBetween(0, 10),
        ];
    }
}
