<?php

use App\Http\Controllers\Api\V1\AiFoodSuggestionController;
use App\Http\Controllers\Api\V1\AuthRegistrationController;
use App\Http\Controllers\Api\V1\AuthTokenController;
use App\Http\Controllers\Api\V1\CurrentUserController;
use App\Http\Controllers\Api\V1\CustomerOrderController;
use App\Http\Controllers\Api\V1\DeliveryFeeController;
use App\Http\Controllers\Api\V1\DriverTrackingController;
use App\Http\Controllers\Api\V1\KitchenOrderTicketController;
use App\Http\Controllers\Api\V1\OrderChatController;
use App\Http\Controllers\Api\V1\OrderRatingController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PhoneLoginController;
use App\Http\Controllers\Api\V1\PhoneVerificationController;
use App\Http\Controllers\Api\V1\RestaurantOrderStatusController;
use App\Http\Controllers\Api\V1\RestaurantStoryController;
use App\Http\Controllers\Api\V1\SearchController;
use App\Http\Controllers\Api\V1\SocialAuthController;
use App\Http\Controllers\Api\V1\UpdateCurrentUserController;
use App\Http\Controllers\Api\V1\UploadController;
use App\Http\Controllers\Api\V1\UserFavoriteRestaurantController;
use App\Http\Controllers\Api\V1\UserNotificationController;
use App\Http\Controllers\Api\V1\UserPaymentMethodController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    Route::post('auth/sign-in', [AuthTokenController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('auth.sign-in');

    Route::post('auth/sign-up', [AuthRegistrationController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('auth.sign-up');

    Route::post('auth/phone/send-otp', [PhoneVerificationController::class, 'sendCode'])
        ->middleware('throttle:5,1')
        ->name('auth.phone.send-otp');

    Route::post('auth/phone/verify-otp', [PhoneVerificationController::class, 'verifyCode'])
        ->middleware('throttle:10,1')
        ->name('auth.phone.verify-otp');

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
    Route::get('deals', [App\Http\Controllers\Api\V1\SpecialOfferController::class, 'index'])->name('deals.index');
    Route::get('special-offers', [App\Http\Controllers\Api\V1\SpecialOfferController::class, 'index'])->name('special-offers.index');
    Route::get('stories', [RestaurantStoryController::class, 'index'])->name('stories.index');
    Route::get('search', SearchController::class)->name('search.index');

    Route::apiResource('categories', \App\Http\Controllers\Api\V1\CategoryController::class)->only(['index', 'show']);
    Route::apiResource('restaurants', \App\Http\Controllers\Api\V1\RestaurantController::class)->only(['index', 'show']);
    Route::get('delivery-fee/estimate', [DeliveryFeeController::class, 'estimate'])->name('delivery-fee.estimate');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('broadcasting/auth', function (Request $request) {
            $authenticatedUser = Auth::guard('sanctum')->user();
            $request->setUserResolver(static fn () => $authenticatedUser);

            return Broadcast::auth($request);
        })->name('broadcasting.auth');

        Route::get('auth/me', CurrentUserController::class)->name('auth.me');
        Route::put('auth/me', UpdateCurrentUserController::class)->name('auth.me.update');
        Route::get('users/me', CurrentUserController::class)->name('users.me');
        Route::match(['put', 'patch'], 'users/me', UpdateCurrentUserController::class)->name('users.me.update');

        Route::get('user/preferences', [\App\Http\Controllers\Api\V1\UserPreferenceController::class, 'show'])->name('user.preferences.show');
        Route::put('user/preferences', [\App\Http\Controllers\Api\V1\UserPreferenceController::class, 'update'])->name('user.preferences.update');

        Route::get('users/me/addresses', [\App\Http\Controllers\Api\V1\UserAddressController::class, 'index'])->name('users.me.addresses.index');
        Route::post('users/me/addresses', [\App\Http\Controllers\Api\V1\UserAddressController::class, 'store'])->name('users.me.addresses.store');
        Route::get('users/me/favorites', [UserFavoriteRestaurantController::class, 'index'])->name('users.me.favorites.index');
        Route::post('users/me/favorites/{restaurant}', [UserFavoriteRestaurantController::class, 'store'])->name('users.me.favorites.store');
        Route::delete('users/me/favorites/{restaurant}', [UserFavoriteRestaurantController::class, 'destroy'])->name('users.me.favorites.destroy');
        Route::get('users/me/payment-methods', [UserPaymentMethodController::class, 'index'])->name('users.me.payment-methods.index');
        Route::post('users/me/payment-methods', [UserPaymentMethodController::class, 'store'])->name('users.me.payment-methods.store');
        Route::apiResource('user/addresses', \App\Http\Controllers\Api\V1\UserAddressController::class);

        Route::delete('auth/token', [AuthTokenController::class, 'destroy'])->name('auth.token.destroy');

        Route::get('notifications', [UserNotificationController::class, 'index'])->name('notifications.index');
        Route::patch('notifications/{notification}/read', [UserNotificationController::class, 'markAsRead'])->name('notifications.read');

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

        Route::get('orders/{order}/tracking', [CustomerOrderController::class, 'show'])
            ->middleware('ability:orders:read')
            ->name('orders.tracking.show');

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

        Route::get('chat/{order}/messages', [OrderChatController::class, 'index'])
            ->middleware('ability:orders:read')
            ->name('chat.messages.index');

        Route::post('chat/{order}/messages', [OrderChatController::class, 'store'])
            ->middleware('ability:orders:write')
            ->name('chat.messages.store');

        Route::get('kitchen-order-tickets', [KitchenOrderTicketController::class, 'index'])
            ->middleware('ability:kot:write')
            ->name('kitchen-order-tickets.index');

        Route::patch('kitchen-order-tickets/{kitchenOrderTicket}', [KitchenOrderTicketController::class, 'update'])
            ->middleware('ability:kot:write')
            ->name('kitchen-order-tickets.update');

        Route::post('stories', [RestaurantStoryController::class, 'store'])
            ->middleware('ability:stories:write')
            ->name('stories.store');

        Route::put('users/me/push-token', [UpdateCurrentUserController::class, 'updatePushToken'])
            ->name('users.me.push-token.update');

        Route::post('orders/{order}/rating', [OrderRatingController::class, 'store'])
            ->middleware('ability:orders:write')
            ->name('orders.rating.store');

        Route::post('orders/{order}/initiate-payment', [PaymentController::class, 'initiate'])
            ->middleware('ability:orders:write')
            ->name('orders.payment.initiate');

        Route::post('upload', [UploadController::class, 'store'])
            ->name('upload.store');
    });

    Route::any('payments/{provider}/callback', [PaymentController::class, 'callback'])
        ->name('payments.callback');
});
