<?php

use App\Http\Controllers\Api\V1\AiFoodSuggestionController;
use App\Http\Controllers\Api\V1\AuthRegistrationController;
use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\CurrentUserController;
use App\Http\Controllers\Api\V1\CustomerOrderController;
use App\Http\Controllers\Api\V1\DriverTrackingController;
use App\Http\Controllers\Api\V1\KitchenOrderTicketController;
use App\Http\Controllers\Api\V1\OrderChatController;
use App\Http\Controllers\Api\V1\PhoneLoginController;
use App\Http\Controllers\Api\V1\PhoneVerificationController;
use App\Http\Controllers\Api\V1\RestaurantOrderStatusController;
use App\Http\Controllers\Api\V1\RestaurantStoryController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SocialAuthController;
use App\Http\Controllers\Api\V1\UpdateCurrentUserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/phone/send-code', [PhoneVerificationController::class, 'sendCode'])
        ->middleware('throttle:5,1')
        ->name('auth.phone.send-code');

    Route::post('auth/phone/verify-code', [PhoneVerificationController::class, 'verifyCode'])
        ->middleware('throttle:10,1')
        ->name('auth.phone.verify-code');

    Route::post('auth/phone/login', [PhoneLoginController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('auth.phone.login');

    Route::post('auth/register', [AuthRegistrationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('auth.register');

    Route::post('auth/forgot-password', [\App\Http\Controllers\Api\V1\AuthPasswordResetController::class, 'sendResetLinkEmail'])
        ->middleware('throttle:5,1')
        ->name('auth.password.email');

    Route::post('auth/reset-password', [\App\Http\Controllers\Api\V1\AuthPasswordResetController::class, 'resetPassword'])
        ->name('auth.password.reset');

    Route::post('auth/social/login', [SocialAuthController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('auth.social.login');

    Route::post('auth/token', [AuthTokenController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('auth.token.store');

    Route::get('banners', [App\Http\Controllers\Api\V1\BannerController::class, 'index'])->name('banners.index');
    Route::get('special-offers', [App\Http\Controllers\Api\V1\SpecialOfferController::class, 'index'])->name('special-offers.index');
    Route::get('stories', [RestaurantStoryController::class, 'index'])->name('stories.index');
    Route::get('search', SearchController::class)->name('search.index');

    Route::apiResource('categories', \App\Http\Controllers\Api\V1\CategoryController::class)->only(['index', 'show']);
    Route::apiResource('restaurants', \App\Http\Controllers\Api\V1\RestaurantController::class)->only(['index', 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('auth/me', CurrentUserController::class)->name('auth.me');
        Route::put('auth/me', UpdateCurrentUserController::class)->name('auth.me.update');

        Route::get('user/preferences', [\App\Http\Controllers\Api\V1\UserPreferenceController::class, 'show'])->name('user.preferences.show');
        Route::put('user/preferences', [\App\Http\Controllers\Api\V1\UserPreferenceController::class, 'update'])->name('user.preferences.update');

        Route::apiResource('user/addresses', \App\Http\Controllers\Api\V1\UserAddressController::class);

        Route::delete('auth/token', [AuthTokenController::class, 'destroy'])->name('auth.token.destroy');

        Route::get('ai/food-suggestions', [AiFoodSuggestionController::class, 'index'])
            ->middleware('ability:orders:read')
            ->name('ai.food-suggestions.index');

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

        Route::get('orders/{order}/chat/messages', [OrderChatController::class, 'index'])
            ->middleware('ability:orders:read')
            ->name('orders.chat.messages.index');

        Route::post('orders/{order}/chat/messages', [OrderChatController::class, 'store'])
            ->middleware('ability:orders:write')
            ->name('orders.chat.messages.store');

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
