<?php

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Support\Facades\Hash;

it('seeds an admin user from admin config', function () {
    config()->set('admin.user', [
        'name' => 'Seeded Admin',
        'email' => 'seeded-admin@example.com',
        'phone' => '+9779811111111',
        'password' => 'SecureAdmin123!',
    ]);

    $this->seed(AdminUserSeeder::class);

    $admin = User::query()->where('email', 'seeded-admin@example.com')->first();

    expect($admin)->not->toBeNull();
    expect($admin?->role)->toBe(UserRole::Admin);
    expect($admin?->email_verified_at)->not->toBeNull();
    expect($admin?->phone_verified_at)->not->toBeNull();
    expect(Hash::check('SecureAdmin123!', (string) $admin?->password))->toBeTrue();
});

it('does not create duplicate admin users when seeded multiple times', function () {
    config()->set('admin.user', [
        'name' => 'Seeded Admin',
        'email' => 'seeded-admin@example.com',
        'phone' => '+9779811111111',
        'password' => 'SecureAdmin123!',
    ]);

    $this->seed(AdminUserSeeder::class);
    $this->seed(AdminUserSeeder::class);

    $this->assertDatabaseCount('users', 1);
});
