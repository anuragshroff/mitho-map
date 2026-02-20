<?php

use App\Enums\KitchenOrderTicketStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Events\KitchenOrderTicketUpdated;
use App\Events\OrderStatusUpdated;
use App\Models\Coupon;
use App\Models\KitchenOrderTicket;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Story;
use App\Models\User;
use Illuminate\Support\Facades\Event;

it('allows admin to assign driver and update order status', function () {
    Event::fake([OrderStatusUpdated::class]);

    $admin = User::factory()->admin()->create();
    $driver = User::factory()->driver()->create();
    $customer = User::factory()->customer()->create();
    $restaurant = Restaurant::factory()->create();

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

    $this->actingAs($admin)
        ->patch(route('admin.orders.assign-driver', $order), [
            'driver_id' => $driver->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'driver_id' => $driver->id,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.orders.update-status', $order), [
            'status' => OrderStatus::Preparing->value,
            'notes' => 'Kitchen started.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Preparing->value,
    ]);

    $this->assertDatabaseHas('kitchen_order_tickets', [
        'order_id' => $order->id,
        'status' => KitchenOrderTicketStatus::InPreparation->value,
    ]);

    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $order->id,
        'to_status' => OrderStatus::Preparing->value,
        'updated_by' => $admin->id,
    ]);

    Event::assertDispatched(OrderStatusUpdated::class);
});

it('allows admin to toggle restaurant availability', function () {
    $admin = User::factory()->admin()->create();
    $restaurant = Restaurant::factory()->create([
        'is_open' => true,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.restaurants.update-availability', $restaurant), [
            'is_open' => false,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('restaurants', [
        'id' => $restaurant->id,
        'is_open' => false,
    ]);
});

it('allows admin to moderate stories', function () {
    $admin = User::factory()->admin()->create();
    $story = Story::factory()->create([
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.stories.update-status', $story), [
            'is_active' => false,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('stories', [
        'id' => $story->id,
        'is_active' => false,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.stories.destroy', $story))
        ->assertRedirect();

    $this->assertDatabaseMissing('stories', [
        'id' => $story->id,
    ]);
});

it('allows admin to manage menu items', function () {
    $admin = User::factory()->admin()->create();
    $restaurant = Restaurant::factory()->create();
    $menuItem = MenuItem::factory()->for($restaurant)->create([
        'is_available' => true,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.menu-items.store'), [
            'restaurant_id' => $restaurant->id,
            'name' => 'Butter Paneer Bowl',
            'description' => 'Creamy paneer with rice',
            'price_cents' => 1299,
            'prep_time_minutes' => 18,
            'is_available' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('menu_items', [
        'restaurant_id' => $restaurant->id,
        'name' => 'Butter Paneer Bowl',
        'price_cents' => 1299,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.menu-items.update', $menuItem), [
            'name' => 'Spicy Noodle Box',
            'description' => 'Chili garlic noodles',
            'price_cents' => 999,
            'prep_time_minutes' => 12,
            'is_available' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('menu_items', [
        'id' => $menuItem->id,
        'name' => 'Spicy Noodle Box',
        'price_cents' => 999,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.menu-items.update-availability', $menuItem), [
            'is_available' => false,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('menu_items', [
        'id' => $menuItem->id,
        'is_available' => false,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.menu-items.destroy', $menuItem))
        ->assertRedirect();

    $this->assertDatabaseMissing('menu_items', [
        'id' => $menuItem->id,
    ]);
});

it('allows admin to manage coupons', function () {
    $admin = User::factory()->admin()->create();
    $restaurant = Restaurant::factory()->create();
    $coupon = Coupon::factory()->create([
        'restaurant_id' => $restaurant->id,
        'code' => 'HELLO10',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.coupons.store'), [
            'restaurant_id' => $restaurant->id,
            'code' => 'SAVE15',
            'title' => 'Save 15 Percent',
            'description' => 'Limited launch offer',
            'discount_type' => 'percentage',
            'discount_value' => 15,
            'minimum_order_cents' => 1000,
            'maximum_discount_cents' => 500,
            'usage_limit' => 100,
            'starts_at' => now()->toIso8601String(),
            'ends_at' => now()->addDays(10)->toIso8601String(),
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('coupons', [
        'code' => 'SAVE15',
        'discount_type' => 'percentage',
        'discount_value' => 15,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.coupons.update', $coupon), [
            'restaurant_id' => $restaurant->id,
            'code' => 'HELLO20',
            'title' => 'Hello 20',
            'description' => 'Updated offer',
            'discount_type' => 'percentage',
            'discount_value' => 20,
            'minimum_order_cents' => 1200,
            'maximum_discount_cents' => 700,
            'usage_limit' => 120,
            'starts_at' => now()->toIso8601String(),
            'ends_at' => now()->addDays(15)->toIso8601String(),
            'is_active' => true,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('coupons', [
        'id' => $coupon->id,
        'code' => 'HELLO20',
        'discount_value' => 20,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.coupons.update-status', $coupon), [
            'is_active' => false,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('coupons', [
        'id' => $coupon->id,
        'is_active' => false,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.coupons.destroy', $coupon))
        ->assertRedirect();

    $this->assertDatabaseMissing('coupons', [
        'id' => $coupon->id,
    ]);
});

it('allows admin to manage kitchen order ticket status', function () {
    Event::fake([OrderStatusUpdated::class, KitchenOrderTicketUpdated::class]);

    $admin = User::factory()->admin()->create();
    $customer = User::factory()->customer()->create();
    $restaurant = Restaurant::factory()->create();

    $order = Order::factory()
        ->for($restaurant, 'restaurant')
        ->for($customer, 'customer')
        ->create([
            'status' => OrderStatus::Pending->value,
        ]);

    $ticket = KitchenOrderTicket::factory()->create([
        'order_id' => $order->id,
        'restaurant_id' => $restaurant->id,
        'status' => KitchenOrderTicketStatus::Pending->value,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.kitchen-order-tickets.update-status', $ticket), [
            'status' => KitchenOrderTicketStatus::Accepted->value,
            'notes' => 'Kitchen accepted.',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('kitchen_order_tickets', [
        'id' => $ticket->id,
        'status' => KitchenOrderTicketStatus::Accepted->value,
        'notes' => 'Kitchen accepted.',
    ]);

    $this->assertDatabaseHas('orders', [
        'id' => $order->id,
        'status' => OrderStatus::Confirmed->value,
    ]);

    $this->assertDatabaseHas('order_status_histories', [
        'order_id' => $order->id,
        'to_status' => OrderStatus::Confirmed->value,
        'updated_by' => $admin->id,
    ]);

    Event::assertDispatched(OrderStatusUpdated::class);
    Event::assertDispatched(KitchenOrderTicketUpdated::class);
});

it('allows admin to update user role and blocks self demotion', function () {
    $admin = User::factory()->admin()->create();
    $driver = User::factory()->driver()->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.update-role', $driver), [
            'role' => UserRole::Customer->value,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('users', [
        'id' => $driver->id,
        'role' => UserRole::Customer->value,
    ]);

    $this->actingAs($admin)
        ->from(route('admin.users.index'))
        ->patch(route('admin.users.update-role', $admin), [
            'role' => UserRole::Driver->value,
        ])
        ->assertRedirect(route('admin.users.index'))
        ->assertSessionHasErrors('role');

    $this->assertDatabaseHas('users', [
        'id' => $admin->id,
        'role' => UserRole::Admin->value,
    ]);
});

it('forbids non admin users from admin management actions', function () {
    $customer = User::factory()->customer()->create();
    $driver = User::factory()->driver()->create();
    $restaurant = Restaurant::factory()->create();
    $order = Order::factory()->for($restaurant, 'restaurant')->create();
    $menuItem = MenuItem::factory()->for($restaurant)->create();
    $coupon = Coupon::factory()->create();
    $ticket = KitchenOrderTicket::factory()->create([
        'order_id' => $order->id,
        'restaurant_id' => $restaurant->id,
    ]);
    $story = Story::factory()->create();

    $this->actingAs($customer)
        ->patch(route('admin.orders.assign-driver', $order), [
            'driver_id' => $driver->id,
        ])
        ->assertForbidden();

    $this->actingAs($customer)
        ->patch(route('admin.orders.update-status', $order), [
            'status' => OrderStatus::Cancelled->value,
        ])
        ->assertForbidden();

    $this->actingAs($customer)
        ->patch(route('admin.restaurants.update-availability', $restaurant), [
            'is_open' => false,
        ])
        ->assertForbidden();

    $this->actingAs($customer)
        ->patch(route('admin.stories.update-status', $story), [
            'is_active' => false,
        ])
        ->assertForbidden();

    $this->actingAs($customer)
        ->patch(route('admin.kitchen-order-tickets.update-status', $ticket), [
            'status' => KitchenOrderTicketStatus::Cancelled->value,
        ])
        ->assertForbidden();

    $this->actingAs($customer)
        ->post(route('admin.menu-items.store'), [
            'restaurant_id' => $restaurant->id,
            'name' => 'Unauthorized Item',
            'price_cents' => 500,
            'prep_time_minutes' => 10,
            'is_available' => true,
        ])
        ->assertForbidden();

    $this->actingAs($customer)
        ->patch(route('admin.menu-items.update', $menuItem), [
            'name' => 'Unauthorized Update',
            'description' => 'Nope',
            'price_cents' => 700,
            'prep_time_minutes' => 11,
            'is_available' => true,
        ])
        ->assertForbidden();

    $this->actingAs($customer)
        ->patch(route('admin.coupons.update-status', $coupon), [
            'is_active' => false,
        ])
        ->assertForbidden();

    $this->actingAs($customer)
        ->patch(route('admin.users.update-role', $driver), [
            'role' => UserRole::Customer->value,
        ])
        ->assertForbidden();

    $this->actingAs($customer)
        ->delete(route('admin.stories.destroy', $story))
        ->assertForbidden();
});
