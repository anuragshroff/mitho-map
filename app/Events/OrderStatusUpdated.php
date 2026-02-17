<?php

namespace App\Events;

use App\Models\Order;
use App\Models\OrderStatusHistory;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Order $order, public ?OrderStatusHistory $history = null) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $channels = [
            new PrivateChannel('orders.'.$this->order->id),
            new PrivateChannel('restaurants.'.$this->order->restaurant_id),
        ];

        if ($this->order->driver_id !== null) {
            $channels[] = new PrivateChannel('drivers.'.$this->order->driver_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'order.status.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'status' => $this->order->status?->value,
            'driver_id' => $this->order->driver_id,
            'updated_at' => now()->toIso8601String(),
            'history' => $this->history === null ? null : [
                'from_status' => $this->history->from_status?->value,
                'to_status' => $this->history->to_status?->value,
                'notes' => $this->history->notes,
                'updated_by' => $this->history->updated_by,
            ],
        ];
    }
}
