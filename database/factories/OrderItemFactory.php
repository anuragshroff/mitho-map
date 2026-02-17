<?php

namespace Database\Factories;

use App\Models\MenuItem;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 4);
        $unitPrice = fake()->numberBetween(199, 2199);

        return [
            'order_id' => Order::factory(),
            'menu_item_id' => MenuItem::factory(),
            'name' => fake()->words(2, true),
            'unit_price_cents' => $unitPrice,
            'quantity' => $quantity,
            'line_total_cents' => $quantity * $unitPrice,
            'special_instructions' => fake()->boolean(20) ? fake()->sentence(4) : null,
        ];
    }
}
