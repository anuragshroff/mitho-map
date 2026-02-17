<?php

namespace Database\Factories;

use App\Enums\KitchenOrderTicketStatus;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\KitchenOrderTicket>
 */
class KitchenOrderTicketFactory extends Factory
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
            'restaurant_id' => Restaurant::factory(),
            'status' => KitchenOrderTicketStatus::Pending->value,
            'notes' => null,
            'accepted_at' => null,
            'ready_at' => null,
        ];
    }
}
