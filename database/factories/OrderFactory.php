<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(900, 4999);
        $deliveryFee = fake()->numberBetween(100, 400);

        return [
            'customer_id' => User::factory()->customer(),
            'restaurant_id' => Restaurant::factory(),
            'driver_id' => null,
            'coupon_id' => null,
            'status' => OrderStatus::Pending->value,
            'subtotal_cents' => $subtotal,
            'delivery_fee_cents' => $deliveryFee,
            'discount_cents' => 0,
            'total_cents' => $subtotal + $deliveryFee,
            'delivery_address' => fake()->streetAddress().', '.fake()->city(),
            'customer_notes' => fake()->boolean(25) ? fake()->sentence() : null,
            'placed_at' => now(),
            'delivered_at' => null,
        ];
    }
}
