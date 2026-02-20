<?php

namespace App\Events;

use App\Models\KitchenOrderTicket;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class KitchenOrderTicketUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public KitchenOrderTicket $ticket) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('orders.'.$this->ticket->order_id),
            new PrivateChannel('restaurants.'.$this->ticket->restaurant_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'kitchen.order.ticket.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'order_id' => $this->ticket->order_id,
            'restaurant_id' => $this->ticket->restaurant_id,
            'status' => $this->ticket->status?->value,
            'notes' => $this->ticket->notes,
            'accepted_at' => $this->ticket->accepted_at?->toIso8601String(),
            'ready_at' => $this->ticket->ready_at?->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
