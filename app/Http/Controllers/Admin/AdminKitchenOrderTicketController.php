<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KitchenOrderTicketStatus;
use App\Enums\OrderStatus;
use App\Events\KitchenOrderTicketUpdated;
use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminKitchenOrderTicketStatusRequest;
use App\Models\KitchenOrderTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminKitchenOrderTicketController extends Controller
{
    public function index(Request $request): Response
    {
        $query = KitchenOrderTicket::query()
            ->with([
                'order:id,status,total_cents,customer_id',
                'order.customer:id,name',
                'restaurant:id,name',
            ])
            ->latest('id');

        $status = $request->string('status')->toString();
        $search = trim($request->string('search')->toString());

        if ($status !== '' && in_array($status, $this->statusValues(), true)) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($ticketQuery) use ($search): void {
                $ticketQuery
                    ->where('id', 'like', '%'.$search.'%')
                    ->orWhere('order_id', 'like', '%'.$search.'%')
                    ->orWhereHas('restaurant', function ($restaurantQuery) use ($search): void {
                        $restaurantQuery->where('name', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('order.customer', function ($customerQuery) use ($search): void {
                        $customerQuery->where('name', 'like', '%'.$search.'%');
                    });
            });
        }

        $kitchenOrderTickets = $query
            ->paginate(20)
            ->withQueryString()
            ->through(function (KitchenOrderTicket $ticket): array {
                return [
                    'id' => $ticket->id,
                    'order_id' => $ticket->order_id,
                    'restaurant_id' => $ticket->restaurant_id,
                    'status' => $ticket->status?->value,
                    'notes' => $ticket->notes,
                    'accepted_at' => $ticket->accepted_at?->toIso8601String(),
                    'ready_at' => $ticket->ready_at?->toIso8601String(),
                    'restaurant_name' => $ticket->restaurant?->name,
                    'customer_name' => $ticket->order?->customer?->name,
                    'order_status' => $ticket->order?->status?->value,
                    'order_total_cents' => $ticket->order?->total_cents,
                ];
            });

        $statusCounts = KitchenOrderTicket::query()
            ->selectRaw('status, COUNT(*) AS aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(function (int $count): int {
                return $count;
            })
            ->all();

        return Inertia::render('admin/kitchen-order-tickets', [
            'kitchenOrderTickets' => $kitchenOrderTickets,
            'statusCounts' => $statusCounts,
            'statusOptions' => $this->statusValues(),
            'filters' => [
                'search' => $search,
                'status' => $status,
            ],
        ]);
    }

    public function updateStatus(
        UpdateAdminKitchenOrderTicketStatusRequest $request,
        KitchenOrderTicket $kitchenOrderTicket,
    ): RedirectResponse {
        /** @var KitchenOrderTicketStatus $status */
        $status = $request->enum('status', KitchenOrderTicketStatus::class);

        $kitchenOrderTicket->status = $status;
        $kitchenOrderTicket->notes = $request->string('notes')->toString() ?: null;

        if ($status === KitchenOrderTicketStatus::Accepted && $kitchenOrderTicket->accepted_at === null) {
            $kitchenOrderTicket->accepted_at = now();
        }

        if ($status === KitchenOrderTicketStatus::Ready && $kitchenOrderTicket->ready_at === null) {
            $kitchenOrderTicket->ready_at = now();
        }

        $kitchenOrderTicket->save();
        KitchenOrderTicketUpdated::dispatch($kitchenOrderTicket);

        $this->syncOrderStatus($request, $kitchenOrderTicket, $status);

        return back();
    }

    protected function syncOrderStatus(
        Request $request,
        KitchenOrderTicket $kitchenOrderTicket,
        KitchenOrderTicketStatus $status,
    ): void {
        $order = $kitchenOrderTicket->order;
        $orderStatus = $this->getOrderStatusFromKitchenStatus($status);

        if ($order === null || $orderStatus === null || $order->status === $orderStatus) {
            return;
        }

        $previousStatus = $order->status;

        $order->status = $orderStatus;
        $order->save();

        $history = $order->statusHistories()->create([
            'updated_by' => $request->user()?->id,
            'from_status' => $previousStatus,
            'to_status' => $orderStatus,
            'notes' => 'Updated from admin KOT panel.',
        ]);

        OrderStatusUpdated::dispatch($order, $history);
    }

    protected function getOrderStatusFromKitchenStatus(KitchenOrderTicketStatus $status): ?OrderStatus
    {
        return match ($status) {
            KitchenOrderTicketStatus::Pending => OrderStatus::Pending,
            KitchenOrderTicketStatus::Accepted => OrderStatus::Confirmed,
            KitchenOrderTicketStatus::InPreparation => OrderStatus::Preparing,
            KitchenOrderTicketStatus::Ready => OrderStatus::ReadyForPickup,
            KitchenOrderTicketStatus::Completed => OrderStatus::OutForDelivery,
            KitchenOrderTicketStatus::Cancelled => OrderStatus::Cancelled,
        };
    }

    /**
     * @return array<int, string>
     */
    protected function statusValues(): array
    {
        return collect(KitchenOrderTicketStatus::cases())
            ->map(fn (KitchenOrderTicketStatus $status): string => $status->value)
            ->values()
            ->all();
    }
}
