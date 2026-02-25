<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\CouponDiscountType;
use App\Enums\KitchenOrderTicketStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Coupon;
use App\Models\KitchenOrderTicket;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Restaurant;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\DistanceCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerOrderController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $query = Order::query()
            ->with(['items', 'restaurant', 'driver', 'coupon', 'kitchenOrderTicket'])
            ->latest('id');

        if ($user->role === UserRole::Customer) {
            $query->where('customer_id', $user->id);
        }

        if ($user->role === UserRole::Restaurant) {
            $query->whereHas('restaurant', function ($restaurantQuery) use ($user): void {
                $restaurantQuery->where('owner_id', $user->id);
            });
        }

        if ($user->role === UserRole::Driver) {
            $query->where('driver_id', $user->id);
        }

        return OrderResource::collection($query->paginate(20));
    }

    /**
     * @throws ValidationException
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validated();

        $restaurant = Restaurant::query()->findOrFail($validated['restaurant_id']);

        $menuItems = MenuItem::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('is_available', true)
            ->whereIn('id', collect($validated['items'])->pluck('menu_item_id')->all())
            ->get()
            ->keyBy('id');

        $this->ensureRequestedItemsAreAvailable(
            collect($validated['items']),
            $menuItems,
        );

        $deliveryFeeCents = $this->calculateDeliveryFee($validated, $restaurant);
        $subtotalCents = 0;
        $lineItems = [];

        foreach ($validated['items'] as $lineItem) {
            $menuItem = $menuItems->get($lineItem['menu_item_id']);
            $quantity = (int) $lineItem['quantity'];
            $lineTotalCents = $menuItem->price_cents * $quantity;
            $subtotalCents += $lineTotalCents;

            $lineItems[] = [
                'menu_item_id' => $menuItem->id,
                'name' => $menuItem->name,
                'unit_price_cents' => $menuItem->price_cents,
                'quantity' => $quantity,
                'line_total_cents' => $lineTotalCents,
                'special_instructions' => $lineItem['special_instructions'] ?? null,
            ];
        }

        $orderAmountCents = $subtotalCents + $deliveryFeeCents;
        $coupon = $this->resolveCoupon(
            $validated['coupon_code'] ?? null,
            $restaurant,
            $orderAmountCents,
        );
        $discountCents = $coupon === null
            ? 0
            : $this->calculateDiscountCents($coupon, $orderAmountCents);
        $totalCents = max($orderAmountCents - $discountCents, 0);

        /**
         * @var array{order: Order, history: OrderStatusHistory} $createdRecords
         */
        $createdRecords = DB::transaction(function () use (
            $validated,
            $user,
            $restaurant,
            $coupon,
            $subtotalCents,
            $deliveryFeeCents,
            $discountCents,
            $totalCents,
            $lineItems
        ): array {
            if ($coupon !== null) {
                $couponUpdated = Coupon::query()
                    ->whereKey($coupon->id)
                    ->where('is_active', true)
                    ->when($coupon->usage_limit !== null, function ($query): void {
                        $query->whereColumn('usage_count', '<', 'usage_limit');
                    })
                    ->increment('usage_count');

                if ($couponUpdated === 0) {
                    throw ValidationException::withMessages([
                        'coupon_code' => 'Coupon usage limit reached or coupon is no longer active.',
                    ]);
                }
            }

            $order = Order::query()->create([
                'customer_id' => $user->id,
                'restaurant_id' => $restaurant->id,
                'coupon_id' => $coupon?->id,
                'status' => OrderStatus::Pending,
                'subtotal_cents' => $subtotalCents,
                'delivery_fee_cents' => $deliveryFeeCents,
                'discount_cents' => $discountCents,
                'total_cents' => $totalCents,
                'delivery_address' => $validated['delivery_address'],
                'customer_notes' => $validated['customer_notes'] ?? null,
                'placed_at' => now(),
            ]);

            $order->items()->createMany($lineItems);

            KitchenOrderTicket::query()->create([
                'order_id' => $order->id,
                'restaurant_id' => $restaurant->id,
                'status' => KitchenOrderTicketStatus::Pending,
            ]);

            $history = OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'updated_by' => $user->id,
                'from_status' => null,
                'to_status' => OrderStatus::Pending,
                'notes' => 'Order placed by customer.',
            ]);

            return [
                'order' => $order,
                'history' => $history,
            ];
        });

        $order = $createdRecords['order'];
        $history = $createdRecords['history'];

        $order->load([
            'items',
            'customer',
            'driver',
            'restaurant',
            'coupon',
            'kitchenOrderTicket',
            'statusHistories',
            'trackingUpdates',
        ]);

        OrderStatusUpdated::dispatch($order, $history);

        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    public function show(Order $order, Request $request): OrderResource
    {
        /** @var User $user */
        $user = $request->user();

        $this->ensureUserCanViewOrder($order, $user);

        $order->load([
            'items',
            'customer',
            'driver',
            'restaurant',
            'coupon',
            'kitchenOrderTicket',
            'statusHistories',
            'trackingUpdates',
        ]);

        return new OrderResource($order);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $requestedItems
     * @param  Collection<int, MenuItem>  $availableItems
     *
     * @throws ValidationException
     */
    protected function ensureRequestedItemsAreAvailable(Collection $requestedItems, Collection $availableItems): void
    {
        $requestedIds = $requestedItems->pluck('menu_item_id')->sort()->values();
        $availableIds = $availableItems->keys()->sort()->values();

        if ($requestedIds->all() !== $availableIds->all()) {
            throw ValidationException::withMessages([
                'items' => 'Some selected menu items are unavailable for this restaurant.',
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    protected function resolveCoupon(
        ?string $couponCode,
        Restaurant $restaurant,
        int $orderAmountCents,
    ): ?Coupon {
        $normalizedCode = strtoupper(trim((string) $couponCode));

        if ($normalizedCode === '') {
            return null;
        }

        $coupon = Coupon::query()
            ->where('code', $normalizedCode)
            ->first();

        if ($coupon === null) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Coupon code does not exist.',
            ]);
        }

        if (! $coupon->is_active) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Coupon is inactive.',
            ]);
        }

        if ($coupon->restaurant_id !== null && $coupon->restaurant_id !== $restaurant->id) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Coupon is not valid for this restaurant.',
            ]);
        }

        if ($coupon->starts_at !== null && $coupon->starts_at->isFuture()) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Coupon is not active yet.',
            ]);
        }

        if ($coupon->ends_at !== null && $coupon->ends_at->isPast()) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Coupon has expired.',
            ]);
        }

        if ($coupon->usage_limit !== null && $coupon->usage_count >= $coupon->usage_limit) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Coupon usage limit reached.',
            ]);
        }

        if ($coupon->minimum_order_cents !== null && $orderAmountCents < $coupon->minimum_order_cents) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Order amount is below coupon minimum.',
            ]);
        }

        return $coupon;
    }

    protected function calculateDiscountCents(Coupon $coupon, int $orderAmountCents): int
    {
        $discountCents = match ($coupon->discount_type) {
            CouponDiscountType::Percentage => (int) floor(
                ($orderAmountCents * $coupon->discount_value) / 100
            ),
            CouponDiscountType::Fixed => $coupon->discount_value,
        };

        if ($coupon->maximum_discount_cents !== null) {
            $discountCents = min($discountCents, $coupon->maximum_discount_cents);
        }

        return max(0, min($discountCents, $orderAmountCents));
    }

    protected function ensureUserCanViewOrder(Order $order, User $user): void
    {
        if ($user->role === UserRole::Admin) {
            return;
        }

        if ($order->customer_id === $user->id) {
            return;
        }

        if ($order->driver_id === $user->id) {
            return;
        }

        if ($user->role === UserRole::Restaurant) {
            $ownsRestaurant = $order->restaurant()->where('owner_id', $user->id)->exists();

            if ($ownsRestaurant) {
                return;
            }
        }

        abort(403);
    }

    /**
     * Calculate delivery fee based on distance between customer and restaurant.
     *
     * @param  array<string, mixed>  $validated
     */
    protected function calculateDeliveryFee(array $validated, Restaurant $restaurant): int
    {
        $userLat = $validated['latitude'] ?? null;
        $userLng = $validated['longitude'] ?? null;

        if ($userLat === null || $userLng === null || $restaurant->latitude === null || $restaurant->longitude === null) {
            return SystemSetting::getInt('delivery_base_fee_cents', 3000);
        }

        $distanceKm = DistanceCalculator::haversine(
            (float) $restaurant->latitude,
            (float) $restaurant->longitude,
            (float) $userLat,
            (float) $userLng,
        );

        $baseFee = SystemSetting::getInt('delivery_base_fee_cents', 3000);
        $perKm = SystemSetting::getInt('delivery_per_km_cents', 1500);

        return $baseFee + (int) ceil($distanceKm * $perKm);
    }
}
