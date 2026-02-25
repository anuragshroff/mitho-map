<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminMenuItemRequest;
use App\Http\Requests\Admin\UpdateAdminMenuItemAvailabilityRequest;
use App\Http\Requests\Admin\UpdateAdminMenuItemRequest;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminMenuItemController extends Controller
{
    public function index(Request $request): Response
    {
        $query = MenuItem::query()
            ->with(['restaurant:id,name,slug,owner_id', 'restaurant.owner:id,name'])
            ->withCount('orderItems')
            ->latest('id');

        $search = trim($request->string('search')->toString());
        $restaurantId = $request->string('restaurant_id')->toString();
        $isAvailable = $request->string('is_available')->toString();

        if ($search !== '') {
            $query->where(function ($menuQuery) use ($search): void {
                $menuQuery
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhereHas('restaurant', function ($restaurantQuery) use ($search): void {
                        $restaurantQuery->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        if ($restaurantId !== '' && ctype_digit($restaurantId)) {
            $query->where('restaurant_id', (int) $restaurantId);
        }

        if (in_array($isAvailable, ['1', '0'], true)) {
            $query->where('is_available', $isAvailable === '1');
        }

        $menuItems = $query
            ->paginate(20)
            ->withQueryString()
            ->through(function (MenuItem $menuItem): array {
                return [
                    'id' => $menuItem->id,
                    'restaurant_id' => $menuItem->restaurant_id,
                    'restaurant_name' => $menuItem->restaurant?->name,
                    'restaurant_slug' => $menuItem->restaurant?->slug,
                    'owner_name' => $menuItem->restaurant?->owner?->name,
                    'name' => $menuItem->name,
                    'description' => $menuItem->description,
                    'price_cents' => $menuItem->price_cents,
                    'prep_time_minutes' => $menuItem->prep_time_minutes,
                    'is_available' => $menuItem->is_available,
                    'image_url' => $menuItem->image_url,
                    'order_items_count' => $menuItem->order_items_count,
                ];
            });

        $restaurantOptions = Restaurant::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (Restaurant $restaurant): array {
                return [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                ];
            })
            ->values()
            ->all();

        $availabilityCounts = MenuItem::query()
            ->selectRaw('is_available, COUNT(*) AS aggregate')
            ->groupBy('is_available')
            ->pluck('aggregate', 'is_available')
            ->map(function (int $count): int {
                return $count;
            })
            ->all();

        return Inertia::render('admin/menu-items', [
            'menuItems' => $menuItems,
            'restaurantOptions' => $restaurantOptions,
            'availabilityCounts' => $availabilityCounts,
            'filters' => [
                'search' => $search,
                'restaurant_id' => $restaurantId,
                'is_available' => $isAvailable,
            ],
        ]);
    }

    public function store(StoreAdminMenuItemRequest $request): RedirectResponse
    {
        MenuItem::query()->create($request->validated());

        return back();
    }

    public function update(
        UpdateAdminMenuItemRequest $request,
        MenuItem $menuItem,
    ): RedirectResponse {
        $menuItem->update($request->validated());

        return back();
    }

    public function updateAvailability(
        UpdateAdminMenuItemAvailabilityRequest $request,
        MenuItem $menuItem,
    ): RedirectResponse {
        $menuItem->is_available = $request->boolean('is_available');
        $menuItem->save();

        return back();
    }

    public function destroy(MenuItem $menuItem): RedirectResponse
    {
        $menuItem->delete();

        return back();
    }
}
