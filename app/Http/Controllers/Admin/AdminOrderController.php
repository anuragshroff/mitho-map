<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KitchenOrderTicketStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignAdminOrderDriverRequest;
use App\Http\Requests\Admin\UpdateAdminOrderStatusRequest;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminOrderController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Order::query()
            ->with(['customer:id,name', 'restaurant:id,name', 'driver:id,name'])
            ->latest('id');

        $status = $request->string('status')->toString();
        $search = trim($request->string('search')->toString());

        if ($status !== '' && in_array($status, $this->statusValues(), true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($orderQuery) use ($search): void {
                $orderQuery
                    ->where('id', 'like', '%'.$search.'%')
                    ->orWhereHas('customer', function ($customerQuery) use ($search): void {
                        $customerQuery->where('name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('restaurant', function ($restaurantQuery) use ($search): void {
                        $restaurantQuery->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        $orders = $query
            ->paginate(20)
            ->withQueryString()
            ->through(function (Order $order): array {
                return [
                    'id' => $order->id,
                    'status' => $order->status?->value,
                    'customer_name' => $order->customer?->name,
                    'restaurant_name' => $order->restaurant?->name,
                    'driver_id' => $order->driver_id,
                    'driver_name' => $order->driver?->name,
                    'total_cents' => $order->total_cents,
                    'placed_at' => $order->placed_at?->toIso8601String(),
                ];
            });

        $statusCounts = Order::query()
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(function (int $count): int {
                return $count;
            })
            ->all();

        $drivers = User::query()
            ->where('role', UserRole::Driver)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (User $driver): array {
                return [
                    'id' => $driver->id,
                    'name' => $driver->name,
                ];
            })
            ->values()
            ->all();

        return Inertia::render('admin/orders', [
            'orders' => $orders,
            'statusCounts' => $statusCounts,
            'statusOptions' => $this->statusValues(),
            'drivers' => $drivers,
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function updateStatus(
        UpdateAdminOrderStatusRequest $request,
        Order $order,
    ): RedirectResponse {
        /** @var OrderStatus $status */
        $status = $request->enum('status', OrderStatus::class);
        $previousStatus = $order->status;

        if ($previousStatus === $status) {
            return back();
        }

        $order->status = $status;

        if ($status === OrderStatus::Delivered) {
            $order->delivered_at = now();
        }

        if ($status !== OrderStatus::Delivered) {
            $order->delivered_at = null;
        }

        $order->save();

        $history = $order->statusHistories()->create([
            'updated_by' => $request->user()?->id,
            'from_status' => $previousStatus,
            'to_status' => $status,
            'notes' => $request->string('notes')->toString() ?: 'Updated by admin panel.',
        ]);

        $this->syncKitchenTicketStatus($order, $status);

        OrderStatusUpdated::dispatch($order, $history);

        return back();
    }

    public function assignDriver(
        AssignAdminOrderDriverRequest $request,
        Order $order,
    ): RedirectResponse {
        $driverId = $request->integer('driver_id');
        $driverId = $request->filled('driver_id') ? $driverId : null;

        if ($order->driver_id === $driverId) {
            return back();
        }

        $order->driver_id = $driverId;
        $order->save();

        $order->statusHistories()->create([
            'updated_by' => $request->user()?->id,
            'from_status' => $order->status,
            'to_status' => $order->status,
            'notes' => $driverId === null
                ? 'Driver removed by admin.'
                : 'Driver assigned by admin.',
        ]);

        OrderStatusUpdated::dispatch($order);

        return back();
    }

    /**
     * @return array<int, string>
     */
    protected function statusValues(): array
    {
        return collect(OrderStatus::cases())
            ->map(fn (OrderStatus $status): string => $status->value)
            ->values()
            ->all();
    }

    protected function syncKitchenTicketStatus(Order $order, OrderStatus $status): void
    {
        $ticket = $order->kitchenOrderTicket;

        if ($ticket === null) {
            return;
        }

        $ticketStatus = match ($status) {
            OrderStatus::Pending => KitchenOrderTicketStatus::Pending,
            OrderStatus::Confirmed => KitchenOrderTicketStatus::Accepted,
            OrderStatus::Preparing => KitchenOrderTicketStatus::InPreparation,
            OrderStatus::ReadyForPickup => KitchenOrderTicketStatus::Ready,
            OrderStatus::OutForDelivery, OrderStatus::Delivered => KitchenOrderTicketStatus::Completed,
            OrderStatus::Cancelled => KitchenOrderTicketStatus::Cancelled,
        };

        $ticket->status = $ticketStatus;

        if ($ticketStatus === KitchenOrderTicketStatus::Accepted && $ticket->accepted_at === null) {
            $ticket->accepted_at = now();
        }

        if ($ticketStatus === KitchenOrderTicketStatus::Ready && $ticket->ready_at === null) {
            $ticket->ready_at = now();
        }

        $ticket->save();
    }
}
