<?php

use App\Enums\OrderStatus;
use App\Models\Coupon;
use App\Models\KitchenOrderTicket;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Story;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('allows admin to access admin dashboard and management pages', function () {
    $admin = User::factory()->admin()->create();

    $owner = User::factory()->restaurant()->create();
    $restaurant = Restaurant::factory()->for($owner, 'owner')->create();
    $customer = User::factory()->customer()->create();

    MenuItem::factory()->for($restaurant)->create();

    $order = Order::factory()
        ->for($restaurant, 'restaurant')
        ->for($customer, 'customer')
        ->create([
            'status' => OrderStatus::Pending->value,
        ]);

    KitchenOrderTicket::factory()->create([
        'order_id' => $order->id,
        'restaurant_id' => $restaurant->id,
    ]);

    Story::factory()->create([
        'restaurant_id' => $restaurant->id,
        'created_by' => $owner->id,
    ]);

    Coupon::factory()->create([
        'restaurant_id' => $restaurant->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/dashboard')
            ->has('summary')
            ->has('recentOrders')
            ->has('orderStatusCounts'));

    $this->actingAs($admin)
        ->get(route('admin.orders.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/orders')
            ->has('orders.data')
            ->has('statusCounts'));

    $this->actingAs($admin)
        ->get(route('admin.kitchen-order-tickets.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/kitchen-order-tickets')
            ->has('kitchenOrderTickets.data')
            ->has('statusCounts'));

    $this->actingAs($admin)
        ->get(route('admin.menu-items.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/menu-items')
            ->has('menuItems.data')
            ->has('restaurantOptions'));

    $this->actingAs($admin)
        ->get(route('admin.restaurants.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/restaurants')
            ->has('restaurants.data'));

    $this->actingAs($admin)
        ->get(route('admin.stories.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/stories')
            ->has('stories.data'));

    $this->actingAs($admin)
        ->get(route('admin.coupons.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/coupons')
            ->has('coupons.data')
            ->has('discountTypeOptions'));

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/users')
            ->has('users.data')
            ->has('roleCounts'));
});

it('blocks non admin users from admin pages', function () {
    $customer = User::factory()->customer()->create();

    $this->actingAs($customer)
        ->get(route('admin.dashboard'))
        ->assertForbidden();

    $this->actingAs($customer)
        ->get(route('admin.orders.index'))
        ->assertForbidden();

    $this->actingAs($customer)
        ->get(route('admin.kitchen-order-tickets.index'))
        ->assertForbidden();

    $this->actingAs($customer)
        ->get(route('admin.menu-items.index'))
        ->assertForbidden();

    $this->actingAs($customer)
        ->get(route('admin.coupons.index'))
        ->assertForbidden();

    $this->actingAs($customer)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});
