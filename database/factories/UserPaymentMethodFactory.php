<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserPaymentMethod>
 */
class UserPaymentMethodFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => 'card',
            'brand' => fake()->randomElement(['visa', 'mastercard', 'amex']),
            'last_four' => (string) fake()->numberBetween(1000, 9999),
            'exp_month' => fake()->numberBetween(1, 12),
            'exp_year' => fake()->numberBetween((int) now()->format('Y'), (int) now()->format('Y') + 6),
            'token_reference' => fake()->uuid(),
            'is_default' => false,
            'metadata' => [
                'holder_name' => fake()->name(),
            ],
        ];
    }
}
