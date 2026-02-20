<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /**
         * @var array{name: string, email: string, phone: string, password: string} $adminUser
         */
        $adminUser = config('admin.user');

        $admin = User::query()->updateOrCreate(
            ['email' => $adminUser['email']],
            [
                'name' => $adminUser['name'],
                'phone' => $adminUser['phone'],
                'password' => $adminUser['password'],
                'role' => UserRole::Admin,
                'phone_verified_at' => now(),
            ],
        );

        $admin->forceFill([
            'email_verified_at' => now(),
        ])->save();
    }
}
