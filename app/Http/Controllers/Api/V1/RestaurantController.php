<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\RestaurantResource;
use App\Models\Restaurant;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RestaurantController extends Controller
{
    /**
     * Display a listing of the restaurants.
     */
    public function index(): AnonymousResourceCollection
    {
        $restaurants = Restaurant::where('is_active', true)
            ->with(['categories', 'tags'])
            ->get();

        return RestaurantResource::collection($restaurants);
    }

    /**
     * Display the specified restaurant.
     */
    public function show(Restaurant $restaurant): RestaurantResource
    {
        if (! $restaurant->is_active) {
            abort(404);
        }

        $restaurant->load(['categories', 'tags', 'menuItems' => function ($query) {
            $query->where('is_available', true)->orderBy('id');
        }]);

        return new RestaurantResource($restaurant);
    }
}
