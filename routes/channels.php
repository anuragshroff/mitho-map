<?php

use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderConversation;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id): bool {
    return $user->id === $id;
});

Broadcast::channel('orders.{orderId}', function (User $user, int $orderId): bool {
    $order = Order::query()->select(['id', 'customer_id', 'restaurant_id', 'driver_id'])->find($orderId);

    if ($order === null) {
        return false;
    }

    if ($user->role === UserRole::Admin) {
        return true;
    }

    if ($order->customer_id === $user->id) {
        return true;
    }

    if ($order->driver_id === $user->id) {
        return true;
    }

    if ($user->role !== UserRole::Restaurant) {
        return false;
    }

    return Restaurant::query()
        ->whereKey($order->restaurant_id)
        ->where('owner_id', $user->id)
        ->exists();
});

Broadcast::channel('orders.{orderId}.conversation.{conversationType}', function (User $user, int $orderId, string $conversationType): bool {
    if (! in_array($conversationType, OrderConversation::conversationTypeValues(), true)) {
        return false;
    }

    $order = Order::query()->select(['id', 'customer_id', 'driver_id'])->find($orderId);

    if ($order === null) {
        return false;
    }

    if (in_array($conversationType, ['user_driver', 'admin_driver'], true) && $order->driver_id === null) {
        return false;
    }

    return match ($conversationType) {
        'user_driver' => $order->customer_id === $user->id || $order->driver_id === $user->id,
        'user_admin' => $order->customer_id === $user->id || $user->role === UserRole::Admin,
        'admin_driver' => $order->driver_id === $user->id || $user->role === UserRole::Admin,
        default => false,
    };
});

Broadcast::channel('restaurants.{restaurantId}', function (User $user, int $restaurantId): bool {
    if ($user->role === UserRole::Admin) {
        return true;
    }

    if ($user->role !== UserRole::Restaurant) {
        return false;
    }

    return Restaurant::query()
        ->whereKey($restaurantId)
        ->where('owner_id', $user->id)
        ->exists();
});

Broadcast::channel('drivers.{driverId}', function (User $user, int $driverId): bool {
    return $user->role === UserRole::Admin || $user->id === $driverId;
});
