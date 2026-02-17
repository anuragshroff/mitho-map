<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\KitchenOrderTicketStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateOrderStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\User;

class RestaurantOrderStatusController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(UpdateOrderStatusRequest $request, Order $order): OrderResource
    {
        /** @var User $user */
        $user = $request->user();

        $this->ensureUserCanUpdateOrder($order, $user);

        /** @var OrderStatus $status */
        $status = $request->enum('status', OrderStatus::class);
        $notes = $request->string('notes')->toString();
        $previousStatus = $order->status;

        $order->status = $status;

        if ($status === OrderStatus::OutForDelivery && $user->role === UserRole::Driver) {
            $order->driver_id = $user->id;
        }

        if ($status === OrderStatus::Delivered) {
            $order->delivered_at = now();
        }

        if ($status !== OrderStatus::Delivered) {
            $order->delivered_at = null;
        }

        $order->save();

        $history = $order->statusHistories()->create([
            'updated_by' => $user->id,
            'from_status' => $previousStatus,
            'to_status' => $status,
            'notes' => $notes !== '' ? $notes : null,
        ]);

        $this->syncKitchenTicketStatus($order, $status, $notes);

        $order->load([
            'items',
            'customer',
            'driver',
            'restaurant',
            'kitchenOrderTicket',
            'statusHistories',
            'trackingUpdates',
        ]);

        OrderStatusUpdated::dispatch($order, $history);

        return new OrderResource($order);
    }

    protected function ensureUserCanUpdateOrder(Order $order, User $user): void
    {
        if ($user->role === UserRole::Admin) {
            return;
        }

        if ($user->role === UserRole::Driver && $order->driver_id === $user->id) {
            return;
        }

        if ($user->role === UserRole::Restaurant) {
            $ownsRestaurant = $order->restaurant()->where('owner_id', $user->id)->exists();

            if ($ownsRestaurant) {
                return;
            }
        }

        abort(403);
    }

    protected function syncKitchenTicketStatus(Order $order, OrderStatus $status, string $notes): void
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

        if ($notes !== '') {
            $ticket->notes = $notes;
        }

        $ticket->save();
    }
}
