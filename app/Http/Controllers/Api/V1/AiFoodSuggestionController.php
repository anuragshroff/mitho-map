<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\SpecialOffer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AiFoodSuggestionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $preferences = $user->preferences()->first();
        $signals = [
            'dietary_preferences' => $preferences?->dietary_preferences ?? [],
            'favorite_cuisines' => $preferences?->favorite_cuisines ?? [],
            'spice_level' => $preferences?->spice_level,
        ];

        $terms = collect([
            ...$signals['dietary_preferences'],
            ...$signals['favorite_cuisines'],
            $signals['spice_level'],
        ])
            ->filter(fn (mixed $value): bool => is_string($value) && trim($value) !== '')
            ->map(fn (string $value): string => strtolower(trim($value)))
            ->unique()
            ->values();

        $baseQuery = MenuItem::query()
            ->with([
                'restaurant:id,name,is_open,is_active',
            ])
            ->withCount('orderItems')
            ->where('is_available', true)
            ->whereHas('restaurant', function ($restaurantQuery): void {
                $restaurantQuery
                    ->where('is_active', true)
                    ->where('is_open', true);
            });

        $preferredItems = $terms->isEmpty()
            ? collect()
            : $this->preferredItems($terms, $baseQuery);

        $preferredIds = $preferredItems->pluck('id')->all();
        $limit = max(12 - count($preferredIds), 0);

        $trendingItems = $limit === 0
            ? collect()
            : (clone $baseQuery)
                ->when($preferredIds !== [], function ($query) use ($preferredIds): void {
                    $query->whereNotIn('id', $preferredIds);
                })
                ->orderByDesc('order_items_count')
                ->latest('id')
                ->limit($limit)
                ->get();

        $offerRestaurantIds = $this->activeOfferRestaurantIds();

        $suggestions = $preferredItems
            ->concat($trendingItems)
            ->unique('id')
            ->values()
            ->map(function (MenuItem $item) use ($offerRestaurantIds, $preferredIds): array {
                $hasOffer = in_array($item->restaurant_id, $offerRestaurantIds, true);
                $isPreferred = in_array($item->id, $preferredIds, true);

                return [
                    'menu_item_id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'price_cents' => $item->price_cents,
                    'prep_time_minutes' => $item->prep_time_minutes,
                    'restaurant' => [
                        'id' => $item->restaurant?->id,
                        'name' => $item->restaurant?->name,
                    ],
                    'offer_boost' => $hasOffer,
                    'reason' => $isPreferred ? 'preference_match' : 'trending',
                ];
            })
            ->all();

        return response()->json([
            'data' => $suggestions,
            'signals' => $signals,
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    /**
     * @param  Collection<int, string>  $terms
     */
    protected function preferredItems(Collection $terms, $baseQuery): Collection
    {
        return (clone $baseQuery)
            ->where(function ($query) use ($terms): void {
                foreach ($terms as $term) {
                    $query
                        ->orWhere('name', 'like', '%'.$term.'%')
                        ->orWhere('description', 'like', '%'.$term.'%')
                        ->orWhereHas('restaurant.categories', function ($categoryQuery) use ($term): void {
                            $categoryQuery
                                ->where('name', 'like', '%'.$term.'%')
                                ->orWhere('slug', 'like', '%'.$term.'%');
                        });
                }
            })
            ->orderByDesc('order_items_count')
            ->latest('id')
            ->limit(8)
            ->get();
    }

    /**
     * @return array<int, int>
     */
    protected function activeOfferRestaurantIds(): array
    {
        $now = now();

        return SpecialOffer::query()
            ->where('is_active', true)
            ->where(function ($query) use ($now): void {
                $query
                    ->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($query) use ($now): void {
                $query
                    ->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $now);
            })
            ->pluck('restaurant_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->values()
            ->all();
    }
}
