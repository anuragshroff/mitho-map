<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\KitchenOrderTicketStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Events\KitchenOrderTicketUpdated;
use App\Events\OrderStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateKitchenOrderTicketRequest;
use App\Http\Resources\KitchenOrderTicketResource;
use App\Models\KitchenOrderTicket;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class KitchenOrderTicketController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        /** @var User $user */
        $user = $request->user();

        $query = KitchenOrderTicket::query()->with(['order', 'restaurant'])->latest('id');

        if ($user->role === UserRole::Restaurant) {
            $query->whereHas('restaurant', function ($restaurantQuery) use ($user): void {
                $restaurantQuery->where('owner_id', $user->id);
            });
        }

        if (! in_array($user->role, [UserRole::Admin, UserRole::Restaurant], true)) {
            abort(403);
        }

        return KitchenOrderTicketResource::collection($query->paginate(20));
    }

    public function update(
        UpdateKitchenOrderTicketRequest $request,
        KitchenOrderTicket $kitchenOrderTicket,
    ): KitchenOrderTicketResource {
        /** @var User $user */
        $user = $request->user();

        $this->ensureUserCanManageKitchenTicket($kitchenOrderTicket, $user);

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

        $orderStatus = $this->getOrderStatusFromKitchenStatus($status);

        if ($orderStatus !== null) {
            $order = $kitchenOrderTicket->order;
            $previousStatus = $order->status;

            if ($previousStatus !== $orderStatus) {
                $order->status = $orderStatus;
                $order->save();

                $history = $order->statusHistories()->create([
                    'updated_by' => $user->id,
                    'from_status' => $previousStatus,
                    'to_status' => $orderStatus,
                    'notes' => 'Updated from KOT service.',
                ]);

                OrderStatusUpdated::dispatch($order, $history);
            }
        }

        return new KitchenOrderTicketResource($kitchenOrderTicket);
    }

    protected function ensureUserCanManageKitchenTicket(KitchenOrderTicket $kitchenOrderTicket, User $user): void
    {
        if ($user->role === UserRole::Admin) {
            return;
        }

        if ($user->role !== UserRole::Restaurant) {
            abort(403);
        }

        $ownsRestaurant = $kitchenOrderTicket->restaurant()->where('owner_id', $user->id)->exists();

        if (! $ownsRestaurant) {
            abort(403);
        }
    }

    protected function getOrderStatusFromKitchenStatus(KitchenOrderTicketStatus $status): ?OrderStatus
    {
        return match ($status) {
            KitchenOrderTicketStatus::Accepted => OrderStatus::Confirmed,
            KitchenOrderTicketStatus::InPreparation => OrderStatus::Preparing,
            KitchenOrderTicketStatus::Ready => OrderStatus::ReadyForPickup,
            KitchenOrderTicketStatus::Cancelled => OrderStatus::Cancelled,
            default => null,
        };
    }
}
