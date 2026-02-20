<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SearchCatalogRequest;
use App\Models\Category;
use App\Models\MenuItem;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    public function __invoke(SearchCatalogRequest $request): JsonResponse
    {
        $query = $request->validated('q');
        $limit = (int) ($request->validated('limit') ?? 6);
        $searchPattern = '%'.$query.'%';

        $restaurants = Restaurant::query()
            ->where('is_active', true)
            ->where(function (Builder $restaurantQuery) use ($searchPattern): void {
                $restaurantQuery
                    ->where('name', 'like', $searchPattern)
                    ->orWhere('slug', 'like', $searchPattern)
                    ->orWhere('city', 'like', $searchPattern)
                    ->orWhereHas('categories', function (Builder $categoryQuery) use ($searchPattern): void {
                        $categoryQuery
                            ->where('is_active', true)
                            ->where(function (Builder $query) use ($searchPattern): void {
                                $query
                                    ->where('name', 'like', $searchPattern)
                                    ->orWhere('slug', 'like', $searchPattern);
                            });
                    });
            })
            ->with([
                'categories' => function ($categoryQuery): void {
                    $categoryQuery->where('is_active', true);
                },
                'tags',
            ])
            ->orderByDesc('is_open')
            ->orderBy('name')
            ->limit($limit)
            ->get();

        $categories = Category::query()
            ->where('is_active', true)
            ->where(function (Builder $categoryQuery) use ($searchPattern): void {
                $categoryQuery
                    ->where('name', 'like', $searchPattern)
                    ->orWhere('slug', 'like', $searchPattern);
            })
            ->withCount([
                'restaurants as restaurants_count' => function ($restaurantQuery): void {
                    $restaurantQuery->where('is_active', true);
                },
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->limit($limit)
            ->get();

        $menuItems = MenuItem::query()
            ->where('is_available', true)
            ->whereHas('restaurant', function (Builder $restaurantQuery): void {
                $restaurantQuery->where('is_active', true);
            })
            ->where(function (Builder $menuItemQuery) use ($searchPattern): void {
                $menuItemQuery
                    ->where('name', 'like', $searchPattern)
                    ->orWhere('description', 'like', $searchPattern)
                    ->orWhereHas('restaurant', function (Builder $restaurantQuery) use ($searchPattern): void {
                        $restaurantQuery->where('name', 'like', $searchPattern);
                    })
                    ->orWhereHas('restaurant.categories', function (Builder $categoryQuery) use ($searchPattern): void {
                        $categoryQuery
                            ->where('is_active', true)
                            ->where(function (Builder $query) use ($searchPattern): void {
                                $query
                                    ->where('name', 'like', $searchPattern)
                                    ->orWhere('slug', 'like', $searchPattern);
                            });
                    });
            })
            ->with([
                'restaurant:id,name,slug,is_open,is_active',
            ])
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return response()->json([
            'data' => [
                'query' => $query,
                'restaurants' => $restaurants,
                'categories' => $categories,
                'menu_items' => $menuItems,
            ],
        ]);
    }
}
