<?php

namespace App\Concerns;

use App\Enums\UserRole;
use App\Models\User;

trait ResolvesApiTokenAbilities
{
    /**
     * @return array<int, string>
     */
    protected function resolveAbilitiesForUser(User $user): array
    {
        return match ($user->role) {
            UserRole::Customer => ['orders:read', 'orders:write', 'stories:read'],
            UserRole::Restaurant => ['orders:read', 'orders:write', 'kot:write', 'stories:read', 'stories:write'],
            UserRole::Driver => ['orders:read', 'orders:write', 'tracking:write'],
            UserRole::Admin => ['*'],
            default => [],
        };
    }
}
