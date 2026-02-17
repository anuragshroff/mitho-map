<?php

use App\Enums\KitchenOrderTicketStatus;
use App\Enums\OrderStatus;
use App\Events\DriverLocationUpdated;
use App\Events\OrderStatusUpdated;
use App\Models\Coupon;
use App\Models\KitchenOrderTicket;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Story;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

it('allows a customer to place an order and creates kot ticket', function () {
    Event::fake([OrderStatusUpdated::class]);

    $customer = User::factory()->customer()->create();
    $restaurantOwner = User::factory()->restaurant()->create();
    $restaurant = Restaurant::factory()->for($restaurantOwner, 'owner')->create();

    $menuItemA = MenuItem::factory()->for($restaurant)->create([
        'price_cents' => 1299,
        'is_available' => true,
    ]);
    $menuItemB = MenuItem::factory()->for($restaurant)->create([
        'price_cents' => 899,
        'is_available' => true,
    ]);

    Sanctum::actingAs($customer, ['orders:write']);

    $response = $this->postJson(route('api.v1.orders.store'), [
        'restaurant_id' => $restaurant->id,
        'delivery_address' => '221B Baker Street, London',
        'customer_notes' => 'Ring the bell once.',
        'items' => [
            [
                'menu_item_id' => $menuItemA->id,
                'quantity' => 2,
            ],
            [
                'menu_item_id' => $menuItemB->id,
                'quantity' => 1,
                'special_instructions' => 'Less spicy',
            ],
        ],
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.status', OrderStatus::Pending->value)
        ->assertJsonPath('data.restaurant.id', $restaurant->id);

    $orderId = $response->json('data.id');

    expect($orderId)->not->toBeNull();

    $expectedSubtotal = 1299 * 2 + 899;
    $expectedDeliveryFee = (int) config('food.delivery_fee_cents', 500);

    $this->assertDatabaseHas('orders', [
        'id' => $orderId,
        'customer_id' => $customer->id,
        'restaurant_id' => $restaurant->id,
        'status' => OrderStatus::Pending->value,
        'subtotal_cents' => $expectedSubtotal,
        'delivery_fee_cents' => $expectedDeliveryFee,
        'total_cents' => $expectedSubtotal + $expectedDeliveryFee,
    ]);

    $this->assertDatabaseHas('kitchen_order_tickets', [
        'order_id' => $orderId,
        'restaurant_id' => $restaurant->id,
        'status' => KitchenOrderTicketStatus::Pending->value,
    ]);

    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $orderId,
        'to_status' => OrderStatus::Pending->value,
        'updated_by' => $customer->id,
    ]);

    Event::assertDispatched(OrderStatusUpdated::class);
});

it('applies a valid coupon when customer places an order', function () {
    $customer = User::factory()->customer()->create();
    $restaurantOwner = User::factory()->restaurant()->create();
    $restaurant = Restaurant::factory()->for($restaurantOwner, 'owner')->create();

    $menuItem = MenuItem::factory()->for($restaurant)->create([
        'price_cents' => 2000,
        'is_available' => true,
    ]);

    $coupon = Coupon::factory()->create([
        'restaurant_id' => $restaurant->id,
        'code' => 'SAVE10',
        'discount_type' => 'percentage',
        'discount_value' => 10,
        'minimum_order_cents' => 1000,
        'maximum_discount_cents' => 250,
        'usage_limit' => 2,
        'usage_count' => 0,
        'is_active' => true,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
    ]);

    Sanctum::actingAs($customer, ['orders:write']);

    $response = $this->postJson(route('api.v1.orders.store'), [
        'restaurant_id' => $restaurant->id,
        'delivery_address' => '10 Main Street, Springfield',
        'coupon_code' => 'save10',
        'items' => [
            [
                'menu_item_id' => $menuItem->id,
                'quantity' => 1,
            ],
        ],
    ]);

    $deliveryFee = (int) config('food.delivery_fee_cents', 500);
    $expectedDiscount = 250;
    $expectedTotal = 2000 + $deliveryFee - $expectedDiscount;

    $response
        ->assertCreated()
        ->assertJsonPath('data.discount_cents', $expectedDiscount)
        ->assertJsonPath('data.total_cents', $expectedTotal)
        ->assertJsonPath('data.coupon.code', 'SAVE10');

    $orderId = $response->json('data.id');

    $this->assertDatabaseHas('orders', [
        'id' => $orderId,
        'coupon_id' => $coupon->id,
        'discount_cents' => $expectedDiscount,
        'total_cents' => $expectedTotal,
    ]);

    $this->assertDatabaseHas('coupons', [
        'id' => $coupon->id,
        'usage_count' => 1,
    ]);
});

it('allows restaurant owner to update order status', function () {
    Event::fake([OrderStatusUpdated::class]);

    $restaurantOwner = User::factory()->restaurant()->create();
    $customer = User::factory()->customer()->create();
    $restaurant = Restaurant::factory()->for($restaurantOwner, 'owner')->create();

    $order = Order::factory()
        ->for($restaurant, 'restaurant')
        ->for($customer, 'customer')
        ->create([
            'status' => OrderStatus::Pending->value,
        ]);

    KitchenOrderTicket::factory()->create([
        'order_id' => $order->id,
        'restaurant_id' => $restaurant->id,
        'status' => KitchenOrderTicketStatus::Pending->value,
    ]);

    Sanctum::actingAs($restaurantOwner, ['orders:write']);

    $response = $this->patchJson(route('api.v1.orders.status.update', $order), [
        'status' => OrderStatus::Preparing->value,
        'notes' => 'Chef started preparing',
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.status', OrderStatus::Preparing->value);

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Preparing->value,
    ]);

    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $order->id,
        'from_status' => OrderStatus::Pending->value,
        'to_status' => OrderStatus::Preparing->value,
        'updated_by' => $restaurantOwner->id,
    ]);

    $this->assertDatabaseHas('kitchen_order_tickets', [
        'order_id' => $order->id,
        'status' => KitchenOrderTicketStatus::InPreparation->value,
    ]);

    Event::assertDispatched(OrderStatusUpdated::class);
});

it('allows driver to push live tracking updates', function () {
    Event::fake([DriverLocationUpdated::class]);

    $driver = User::factory()->driver()->create();
    $restaurant = Restaurant::factory()->create();
    $customer = User::factory()->customer()->create();

    $order = Order::factory()
        ->for($restaurant, 'restaurant')
        ->for($customer, 'customer')
        ->for($driver, 'driver')
        ->create([
            'status' => OrderStatus::OutForDelivery->value,
        ]);

    Sanctum::actingAs($driver, ['tracking:write']);

    $response = $this->postJson(route('api.v1.orders.tracking.store', $order), [
        'latitude' => 28.7041,
        'longitude' => 77.1025,
        'heading' => 97,
        'speed_kmh' => 34.5,
    ]);

    $response
        ->assertSuccessful()
        ->assertJsonPath('data.order_id', $order->id)
        ->assertJsonPath('data.driver_id', $driver->id);

    $this->assertDatabaseHas('delivery_tracking_updates', [
        'order_id' => $order->id,
        'driver_id' => $driver->id,
    ]);

    Event::assertDispatched(DriverLocationUpdated::class);
});

it('allows restaurant owner to publish story', function () {
    $restaurantOwner = User::factory()->restaurant()->create();
    $restaurant = Restaurant::factory()->for($restaurantOwner, 'owner')->create();

    Sanctum::actingAs($restaurantOwner, ['stories:write']);

    $response = $this->postJson(route('api.v1.stories.store'), [
        'restaurant_id' => $restaurant->id,
        'media_url' => 'https://cdn.example.com/stories/lunch-special.jpg',
        'caption' => 'Fresh lunch combo is live.',
    ]);

    $response
        ->assertCreated()
        ->assertJsonPath('data.restaurant_id', $restaurant->id)
        ->assertJsonPath('data.created_by', $restaurantOwner->id)
        ->assertJsonPath('data.is_active', true);

    $this->assertDatabaseHas('stories', [
        'restaurant_id' => $restaurant->id,
        'created_by' => $restaurantOwner->id,
        'media_url' => 'https://cdn.example.com/stories/lunch-special.jpg',
    ]);

    expect(Story::query()->count())->toBe(1);
});
