<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

it('allows customer and driver to use user_driver conversation', function () {
    $customer = User::factory()->customer()->create();
    $driver = User::factory()->driver()->create();
    $restaurant = Restaurant::factory()->create();

    $order = Order::factory()
        ->for($customer, 'customer')
        ->for($restaurant, 'restaurant')
        ->for($driver, 'driver')
        ->create();

    Sanctum::actingAs($customer, ['orders:read', 'orders:write']);

    $this->postJson(route('api.v1.orders.chat.messages.store', $order), [
        'conversation_type' => 'user_driver',
        'message' => 'Hi driver, please call me on arrival.',
    ])->assertCreated()
        ->assertJsonPath('sender.id', $customer->id)
        ->assertJsonPath('conversation_type', 'user_driver');

    Sanctum::actingAs($driver, ['orders:read', 'orders:write']);

    $this->getJson(route('api.v1.orders.chat.messages.index', [
        'order' => $order->id,
        'conversation_type' => 'user_driver',
    ]))
        ->assertSuccessful()
        ->assertJsonCount(1, 'messages')
        ->assertJsonPath('messages.0.message', 'Hi driver, please call me on arrival.')
        ->assertJsonPath('messages.0.conversation_type', 'user_driver');
});

it('allows customer and admin to use user_admin conversation', function () {
    $customer = User::factory()->customer()->create();
    $admin = User::factory()->admin()->create();
    $driver = User::factory()->driver()->create();
    $restaurant = Restaurant::factory()->create();

    $order = Order::factory()
        ->for($customer, 'customer')
        ->for($restaurant, 'restaurant')
        ->for($driver, 'driver')
        ->create();

    Sanctum::actingAs($customer, ['orders:read', 'orders:write']);
    $this->postJson(route('api.v1.orders.chat.messages.store', $order), [
        'conversation_type' => 'user_admin',
        'message' => 'Need help from support.',
    ])->assertCreated();

    Sanctum::actingAs($admin, ['*']);
    $this->postJson(route('api.v1.orders.chat.messages.store', $order), [
        'conversation_type' => 'user_admin',
        'message' => 'Support here. We are checking.',
    ])->assertCreated();

    $this->getJson(route('api.v1.orders.chat.messages.index', [
        'order' => $order->id,
        'conversation_type' => 'user_admin',
    ]))
        ->assertSuccessful()
        ->assertJsonCount(2, 'messages');
});

it('forbids users outside each conversation type', function () {
    $customer = User::factory()->customer()->create();
    $driver = User::factory()->driver()->create();
    $admin = User::factory()->admin()->create();
    $restaurant = Restaurant::factory()->create();

    $order = Order::factory()
        ->for($customer, 'customer')
        ->for($restaurant, 'restaurant')
        ->for($driver, 'driver')
        ->create();

    $outsider = User::factory()->customer()->create();

    Sanctum::actingAs($outsider, ['orders:read', 'orders:write']);

    $this->getJson(route('api.v1.orders.chat.messages.index', [
        'order' => $order->id,
        'conversation_type' => 'user_driver',
    ]))->assertForbidden();

    $this->postJson(route('api.v1.orders.chat.messages.store', $order), [
        'conversation_type' => 'user_driver',
        'message' => 'Trying to inject chat',
    ])->assertForbidden();

    Sanctum::actingAs($driver, ['orders:read', 'orders:write']);
    $this->getJson(route('api.v1.orders.chat.messages.index', [
        'order' => $order->id,
        'conversation_type' => 'user_admin',
    ]))->assertForbidden();

    Sanctum::actingAs($customer, ['orders:read', 'orders:write']);
    $this->getJson(route('api.v1.orders.chat.messages.index', [
        'order' => $order->id,
        'conversation_type' => 'admin_driver',
    ]))->assertForbidden();

    Sanctum::actingAs($admin, ['*']);
    $this->postJson(route('api.v1.orders.chat.messages.store', $order), [
        'conversation_type' => 'admin_driver',
        'message' => 'Hello driver',
    ])->assertCreated();
});

it('prevents assigning a driver who reached active assignment limit', function () {
    config()->set('food.driver_max_active_assignments', 1);

    $admin = User::factory()->admin()->create();
    $driver = User::factory()->driver()->create();

    $restaurant = Restaurant::factory()->create();

    $activeOrder = Order::factory()
        ->for($restaurant, 'restaurant')
        ->for(User::factory()->customer()->create(), 'customer')
        ->for($driver, 'driver')
        ->create([
            'status' => OrderStatus::OutForDelivery->value,
        ]);

    expect($activeOrder->driver_id)->toBe($driver->id);

    $newOrder = Order::factory()
        ->for($restaurant, 'restaurant')
        ->for(User::factory()->customer()->create(), 'customer')
        ->create([
            'status' => OrderStatus::Confirmed->value,
            'driver_id' => null,
        ]);

    $this->actingAs($admin)
        ->from(route('admin.orders.index'))
        ->patch(route('admin.orders.assign-driver', $newOrder), [
            'driver_id' => $driver->id,
        ])
        ->assertRedirect(route('admin.orders.index'))
        ->assertSessionHasErrors('driver_id');

    $this->assertDatabaseHas('orders', [
        'id' => $newOrder->id,
        'driver_id' => null,
    ]);
});

it('allows assigning available driver and stores assignment metadata', function () {
    config()->set('food.driver_max_active_assignments', 1);

    $admin = User::factory()->admin()->create();
    $driver = User::factory()->driver()->create();
    $restaurant = Restaurant::factory()->create();

    Order::factory()
        ->for($restaurant, 'restaurant')
        ->for(User::factory()->customer()->create(), 'customer')
        ->for($driver, 'driver')
        ->create([
            'status' => OrderStatus::Delivered->value,
        ]);

    $newOrder = Order::factory()
        ->for($restaurant, 'restaurant')
        ->for(User::factory()->customer()->create(), 'customer')
        ->create([
            'status' => OrderStatus::Confirmed->value,
            'driver_id' => null,
        ]);

    $this->actingAs($admin)
        ->patch(route('admin.orders.assign-driver', $newOrder), [
            'driver_id' => $driver->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('orders', [
        'id' => $newOrder->id,
        'driver_id' => $driver->id,
        'assigned_by' => $admin->id,
    ]);
});
