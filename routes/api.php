<?php

use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\CustomerOrderController;
use App\Http\Controllers\Api\V1\DriverTrackingController;
use App\Http\Controllers\Api\V1\KitchenOrderTicketController;
use App\Http\Controllers\Api\V1\RestaurantOrderStatusController;
use App\Http\Controllers\Api\V1\RestaurantStoryController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/token', [AuthTokenController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('auth.token.store');

    Route::get('stories', [RestaurantStoryController::class, 'index'])->name('stories.index');

    Route::middleware('auth:sanctum')->group(function () {
        Route::delete('auth/token', [AuthTokenController::class, 'destroy'])->name('auth.token.destroy');

        Route::get('orders', [CustomerOrderController::class, 'index'])
            ->middleware('ability:orders:read')
            ->name('orders.index');

        Route::post('orders', [CustomerOrderController::class, 'store'])
            ->middleware('ability:orders:write')
            ->name('orders.store');

        Route::get('orders/{order}', [CustomerOrderController::class, 'show'])
            ->middleware('ability:orders:read')
            ->name('orders.show');

        Route::patch('orders/{order}/status', RestaurantOrderStatusController::class)
            ->middleware('ability:orders:write')
            ->name('orders.status.update');

        Route::post('orders/{order}/tracking', DriverTrackingController::class)
            ->middleware('ability:tracking:write')
            ->name('orders.tracking.store');

        Route::get('kitchen-order-tickets', [KitchenOrderTicketController::class, 'index'])
            ->middleware('ability:kot:write')
            ->name('kitchen-order-tickets.index');

        Route::patch('kitchen-order-tickets/{kitchenOrderTicket}', [KitchenOrderTicketController::class, 'update'])
            ->middleware('ability:kot:write')
            ->name('kitchen-order-tickets.update');

        Route::post('stories', [RestaurantStoryController::class, 'store'])
            ->middleware('ability:stories:write')
            ->name('stories.store');
    });
});
