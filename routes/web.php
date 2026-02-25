<?php

use App\Http\Controllers\Admin\AdminCouponController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminKitchenOrderTicketController;
use App\Http\Controllers\Admin\AdminMenuItemController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminPosController;
use App\Http\Controllers\Admin\AdminRestaurantController;
use App\Http\Controllers\Admin\AdminStoryController;
use App\Http\Controllers\Admin\AdminSystemSettingController;
use App\Http\Controllers\Admin\AdminUserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', AdminDashboardController::class)->name('dashboard');

        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])
            ->name('orders.update-status');
        Route::patch('orders/{order}/assign-driver', [AdminOrderController::class, 'assignDriver'])
            ->name('orders.assign-driver');

        Route::get('kitchen-order-tickets', [AdminKitchenOrderTicketController::class, 'index'])
            ->name('kitchen-order-tickets.index');
        Route::patch('kitchen-order-tickets/{kitchenOrderTicket}/status', [AdminKitchenOrderTicketController::class, 'updateStatus'])
            ->name('kitchen-order-tickets.update-status');

        Route::get('menu-items', [AdminMenuItemController::class, 'index'])->name('menu-items.index');
        Route::post('menu-items', [AdminMenuItemController::class, 'store'])->name('menu-items.store');
        Route::patch('menu-items/{menuItem}', [AdminMenuItemController::class, 'update'])->name('menu-items.update');
        Route::patch('menu-items/{menuItem}/availability', [AdminMenuItemController::class, 'updateAvailability'])
            ->name('menu-items.update-availability');
        Route::delete('menu-items/{menuItem}', [AdminMenuItemController::class, 'destroy'])->name('menu-items.destroy');

        Route::get('restaurants', [AdminRestaurantController::class, 'index'])->name('restaurants.index');
        Route::patch('restaurants/{restaurant}/availability', [AdminRestaurantController::class, 'updateAvailability'])
            ->name('restaurants.update-availability');

        Route::get('stories', [AdminStoryController::class, 'index'])->name('stories.index');
        Route::patch('stories/{story}/status', [AdminStoryController::class, 'updateStatus'])
            ->name('stories.update-status');
        Route::delete('stories/{story}', [AdminStoryController::class, 'destroy'])
            ->name('stories.destroy');

        Route::get('banners', [\App\Http\Controllers\Admin\AdminBannerController::class, 'index'])->name('banners.index');
        Route::post('banners', [\App\Http\Controllers\Admin\AdminBannerController::class, 'store'])->name('banners.store');
        Route::patch('banners/{banner}', [\App\Http\Controllers\Admin\AdminBannerController::class, 'update'])->name('banners.update');
        Route::patch('banners/{banner}/status', [\App\Http\Controllers\Admin\AdminBannerController::class, 'updateStatus'])
            ->name('banners.update-status');
        Route::delete('banners/{banner}', [\App\Http\Controllers\Admin\AdminBannerController::class, 'destroy'])->name('banners.destroy');

        Route::get('special-offers', [\App\Http\Controllers\Admin\AdminSpecialOfferController::class, 'index'])->name('special-offers.index');
        Route::post('special-offers', [\App\Http\Controllers\Admin\AdminSpecialOfferController::class, 'store'])->name('special-offers.store');
        Route::patch('special-offers/{specialOffer}', [\App\Http\Controllers\Admin\AdminSpecialOfferController::class, 'update'])->name('special-offers.update');
        Route::patch('special-offers/{specialOffer}/status', [\App\Http\Controllers\Admin\AdminSpecialOfferController::class, 'updateStatus'])
            ->name('special-offers.update-status');
        Route::delete('special-offers/{specialOffer}', [\App\Http\Controllers\Admin\AdminSpecialOfferController::class, 'destroy'])->name('special-offers.destroy');

        Route::get('coupons', [AdminCouponController::class, 'index'])->name('coupons.index');
        Route::post('coupons', [AdminCouponController::class, 'store'])->name('coupons.store');
        Route::patch('coupons/{coupon}', [AdminCouponController::class, 'update'])->name('coupons.update');
        Route::patch('coupons/{coupon}/status', [AdminCouponController::class, 'updateStatus'])
            ->name('coupons.update-status');
        Route::delete('coupons/{coupon}', [AdminCouponController::class, 'destroy'])->name('coupons.destroy');

        Route::get('users', [AdminUserController::class, 'index'])->name('users.index');
        Route::patch('users/{user}/role', [AdminUserController::class, 'updateRole'])
            ->name('users.update-role');

        Route::get('pos', [AdminPosController::class, 'index'])->name('pos.index');
        Route::post('pos', [AdminPosController::class, 'store'])->name('pos.store');

        Route::get('system-settings', [AdminSystemSettingController::class, 'index'])->name('system-settings.index');
        Route::put('system-settings', [AdminSystemSettingController::class, 'update'])->name('system-settings.update');
    });

require __DIR__.'/settings.php';
