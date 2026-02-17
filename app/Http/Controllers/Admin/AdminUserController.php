<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminUserRoleRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::query()->latest('id');

        $search = trim($request->string('search')->toString());
        $role = $request->string('role')->toString();

        if ($search !== '') {
            $query->where(function ($userQuery) use ($search): void {
                $userQuery
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%');
            });
        }

        if ($role !== '' && in_array($role, $this->roleValues(), true)) {
            $query->where('role', $role);
        }

        $users = $query
            ->paginate(20)
            ->withQueryString()
            ->through(function (User $user): array {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role?->value,
                    'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                    'created_at' => $user->created_at?->toIso8601String(),
                ];
            });

        $roleCounts = User::query()
            ->selectRaw('role, COUNT(*) AS aggregate')
            ->groupBy('role')
            ->pluck('aggregate', 'role')
            ->map(function (int $count): int {
                return $count;
            })
            ->all();

        return Inertia::render('admin/users', [
            'users' => $users,
            'roleCounts' => $roleCounts,
            'roleOptions' => $this->roleValues(),
            'currentAdminId' => $request->user()?->id,
            'filters' => [
                'search' => $search,
                'role' => $role,
            ],
        ]);
    }

    public function updateRole(
        UpdateAdminUserRoleRequest $request,
        User $user,
    ): RedirectResponse {
        /** @var UserRole $role */
        $role = $request->enum('role', UserRole::class);

        if ($request->user()?->is($user) && $role !== UserRole::Admin) {
            return back()->withErrors([
                'role' => 'You cannot remove your own admin access.',
            ]);
        }

        if ($user->role === $role) {
            return back();
        }

        $user->role = $role;
        $user->save();

        return back();
    }

    /**
     * @return array<int, string>
     */
    protected function roleValues(): array
    {
        return collect(UserRole::cases())
            ->map(fn (UserRole $role): string => $role->value)
            ->values()
            ->all();
    }
}
