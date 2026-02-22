<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Delivery
            ['key' => 'delivery_base_fee_cents', 'value' => '3000', 'group' => 'delivery'],
            ['key' => 'delivery_per_km_cents', 'value' => '1500', 'group' => 'delivery'],
            ['key' => 'delivery_max_radius_km', 'value' => '15', 'group' => 'delivery'],

            // Payment
            ['key' => 'payment_methods_enabled', 'value' => 'cash', 'group' => 'payment'],
            ['key' => 'payment_esewa_merchant_id', 'value' => null, 'group' => 'payment'],
            ['key' => 'payment_esewa_secret', 'value' => null, 'group' => 'payment'],
            ['key' => 'payment_khalti_public_key', 'value' => null, 'group' => 'payment'],
            ['key' => 'payment_khalti_secret_key', 'value' => null, 'group' => 'payment'],

            // Driver Assignment
            ['key' => 'driver_max_radius_km', 'value' => '10', 'group' => 'driver'],
            ['key' => 'driver_online_timeout_minutes', 'value' => '15', 'group' => 'driver'],
        ];

        foreach ($settings as $setting) {
            SystemSetting::query()->firstOrCreate(
                ['key' => $setting['key']],
                $setting,
            );
        }
    }
}
