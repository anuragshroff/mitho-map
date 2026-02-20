<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    /**
     * Display a listing of the restaurants.
     */
    public function index(Request $request): JsonResponse
    {
        $restaurants = Restaurant::where('is_active', true)
            ->with(['categories', 'tags']) // Assume these relationships exist
            ->get();

        return response()->json([
            'data' => $restaurants,
        ]);
    }

    /**
     * Display the specified restaurant.
     */
    public function show(Restaurant $restaurant): JsonResponse
    {
        if (! $restaurant->is_active) {
            abort(404);
        }

        $restaurant->load(['categories', 'tags', 'menuItems' => function ($query) {
            $query->where('is_available', true);
        }]);

        return response()->json([
            'data' => $restaurant,
        ]);
    }
}
