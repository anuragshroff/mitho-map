<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminRestaurantAvailabilityRequest;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminRestaurantController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Restaurant::query()
            ->with(['owner:id,name'])
            ->withCount(['menuItems', 'orders', 'stories'])
            ->latest('id');

        $search = trim($request->string('search')->toString());
        $isOpen = $request->string('is_open')->toString();

        if ($search !== '') {
            $query->where(function ($restaurantQuery) use ($search): void {
                $restaurantQuery
                    ->where('name', 'like', '%'.$search.'%')
                    ->orWhere('slug', 'like', '%'.$search.'%')
                    ->orWhere('city', 'like', '%'.$search.'%')
                    ->orWhereHas('owner', function ($ownerQuery) use ($search): void {
                        $ownerQuery->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        if (in_array($isOpen, ['1', '0'], true)) {
            $query->where('is_open', $isOpen === '1');
        }

        $restaurants = $query
            ->paginate(20)
            ->withQueryString()
            ->through(function (Restaurant $restaurant): array {
                return [
                    'id' => $restaurant->id,
                    'name' => $restaurant->name,
                    'slug' => $restaurant->slug,
                    'city' => $restaurant->city,
                    'is_open' => $restaurant->is_open,
                    'owner_name' => $restaurant->owner?->name,
                    'menu_items_count' => $restaurant->menu_items_count,
                    'orders_count' => $restaurant->orders_count,
                    'stories_count' => $restaurant->stories_count,
                ];
            });

        return Inertia::render('admin/restaurants', [
            'restaurants' => $restaurants,
            'filters' => [
                'search' => $search,
                'is_open' => $isOpen,
            ],
        ]);
    }

    public function updateAvailability(
        UpdateAdminRestaurantAvailabilityRequest $request,
        Restaurant $restaurant,
    ): RedirectResponse {
        $restaurant->is_open = $request->boolean('is_open');
        $restaurant->save();

        return back();
    }
}
