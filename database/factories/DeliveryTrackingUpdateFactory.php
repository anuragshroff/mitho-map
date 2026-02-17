<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DeliveryTrackingUpdate>
 */
class DeliveryTrackingUpdateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'driver_id' => User::factory()->driver(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'heading' => fake()->numberBetween(0, 360),
            'speed_kmh' => fake()->randomFloat(2, 0, 60),
            'recorded_at' => now(),
        ];
    }
}
