<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\User;
use App\Models\UserFavoriteRestaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserFavoriteRestaurantController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $restaurants = $user->favoriteRestaurants()
            ->where('is_active', true)
            ->with(['categories', 'tags'])
            ->orderByDesc('user_favorite_restaurants.id')
            ->get();

        return response()->json([
            'data' => $restaurants,
        ]);
    }

    public function store(Request $request, Restaurant $restaurant): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $restaurant->is_active) {
            abort(404);
        }

        $favorite = UserFavoriteRestaurant::query()->firstOrCreate([
            'user_id' => $user->id,
            'restaurant_id' => $restaurant->id,
        ]);

        return response()->json([
            'data' => [
                'id' => $favorite->id,
                'restaurant_id' => $restaurant->id,
            ],
            'message' => $favorite->wasRecentlyCreated
                ? 'Restaurant added to favorites.'
                : 'Restaurant is already in favorites.',
        ], $favorite->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, Restaurant $restaurant): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        UserFavoriteRestaurant::query()
            ->where('user_id', $user->id)
            ->where('restaurant_id', $restaurant->id)
            ->delete();

        return response()->json([
            'message' => 'Restaurant removed from favorites.',
        ]);
    }
}
