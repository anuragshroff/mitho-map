<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KitchenOrderTicketStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\KitchenOrderTicket;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Story;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $today = Carbon::today();

        $activeOrderStatuses = [
            OrderStatus::Pending,
            OrderStatus::Confirmed,
            OrderStatus::Preparing,
            OrderStatus::ReadyForPickup,
            OrderStatus::OutForDelivery,
        ];

        $summary = [
            'total_orders' => Order::query()->count(),
            'active_orders' => Order::query()->whereIn('status', $activeOrderStatuses)->count(),
            'delivered_today' => Order::query()
                ->where('status', OrderStatus::Delivered)
                ->whereDate('delivered_at', $today)
                ->count(),
            'today_revenue_cents' => (int) Order::query()
                ->whereDate('placed_at', $today)
                ->whereNot('status', OrderStatus::Cancelled)
                ->sum('total_cents'),
            'open_restaurants' => Restaurant::query()->where('is_open', true)->count(),
            'total_menu_items' => MenuItem::query()->count(),
            'active_stories' => Story::query()
                ->where('is_active', true)
                ->where('expires_at', '>', now())
                ->count(),
            'active_coupons' => Coupon::query()->where('is_active', true)->count(),
            'total_users' => User::query()->count(),
            'online_drivers' => User::query()
                ->where('role', UserRole::Driver)
                ->whereHas('trackingUpdates', function ($trackingQuery): void {
                    $trackingQuery->where('recorded_at', '>=', now()->subMinutes(15));
                })
                ->count(),
            'open_kitchen_tickets' => KitchenOrderTicket::query()
                ->whereIn('status', [
                    KitchenOrderTicketStatus::Pending,
                    KitchenOrderTicketStatus::Accepted,
                    KitchenOrderTicketStatus::InPreparation,
                    KitchenOrderTicketStatus::Ready,
                ])
                ->count(),
        ];

        $recentOrders = Order::query()
            ->with(['customer:id,name', 'restaurant:id,name'])
            ->latest('id')
            ->limit(8)
            ->get()
            ->map(function (Order $order): array {
                return [
                    'id' => $order->id,
                    'status' => $order->status?->value,
                    'total_cents' => $order->total_cents,
                    'placed_at' => $order->placed_at?->toIso8601String(),
                    'customer_name' => $order->customer?->name,
                    'restaurant_name' => $order->restaurant?->name,
                ];
            })
            ->values()
            ->all();

        $orderStatusCounts = Order::query()
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(function (int $count): int {
                return $count;
            })
            ->all();

        return Inertia::render('admin/dashboard', [
            'summary' => $summary,
            'recentOrders' => $recentOrders,
            'orderStatusCounts' => $orderStatusCounts,
        ]);
    }
}
