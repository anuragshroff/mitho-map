<?php

namespace Database\Factories;

use App\Enums\CouponDiscountType;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Coupon>
 */
class CouponFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        /** @var CouponDiscountType $discountType */
        $discountType = fake()->randomElement(CouponDiscountType::cases());
        $discountValue = $discountType === CouponDiscountType::Percentage
            ? fake()->numberBetween(5, 35)
            : fake()->numberBetween(150, 1200);

        $minimumOrderCents = fake()->boolean(65)
            ? fake()->numberBetween(1000, 5000)
            : null;

        $maximumDiscountCents = $discountType === CouponDiscountType::Percentage
            ? fake()->numberBetween(400, 1800)
            : null;

        return [
            'restaurant_id' => fake()->boolean(35) ? Restaurant::factory() : null,
            'code' => 'FD'.Str::upper(fake()->unique()->bothify('??###')),
            'title' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'discount_type' => $discountType->value,
            'discount_value' => $discountValue,
            'minimum_order_cents' => $minimumOrderCents,
            'maximum_discount_cents' => $maximumDiscountCents,
            'starts_at' => now()->subDays(fake()->numberBetween(0, 3)),
            'ends_at' => now()->addDays(fake()->numberBetween(1, 30)),
            'usage_limit' => fake()->boolean(50) ? fake()->numberBetween(10, 300) : null,
            'usage_count' => 0,
            'is_active' => true,
        ];
    }
}
