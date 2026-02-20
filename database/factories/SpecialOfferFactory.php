<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SpecialOffer>
 */
class SpecialOfferFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => \App\Models\Restaurant::factory(),
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'discount_percentage' => fake()->randomElement([10, 15, 20, 25, 50, null]),
            'discount_amount' => fake()->randomElement([5, 10, 15, null]),
            'valid_from' => fake()->dateTimeBetween('-1 week', 'now'),
            'valid_until' => fake()->dateTimeBetween('now', '+1 month'),
            'is_active' => true,
        ];
    }
}
