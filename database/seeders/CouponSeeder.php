<?php

namespace Database\Seeders;

use App\Models\Coupon;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Coupon::factory()->count(8)->create([
            'restaurant_id' => null,
        ]);

        Restaurant::query()
            ->inRandomOrder()
            ->limit(5)
            ->pluck('id')
            ->each(function (int $restaurantId): void {
                Coupon::factory()->count(2)->create([
                    'restaurant_id' => $restaurantId,
                ]);
            });
    }
}
