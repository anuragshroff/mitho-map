<?php

namespace App\Events;

use App\Models\DeliveryTrackingUpdate;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DriverLocationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public Order $order, public DeliveryTrackingUpdate $trackingUpdate) {}

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

        if ($this->trackingUpdate->driver_id !== null) {
            $channels[] = new PrivateChannel('drivers.'.$this->trackingUpdate->driver_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'order.driver.location.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'driver_id' => $this->trackingUpdate->driver_id,
            'latitude' => (float) $this->trackingUpdate->latitude,
            'longitude' => (float) $this->trackingUpdate->longitude,
            'heading' => $this->trackingUpdate->heading === null ? null : (float) $this->trackingUpdate->heading,
            'speed_kmh' => $this->trackingUpdate->speed_kmh === null ? null : (float) $this->trackingUpdate->speed_kmh,
            'recorded_at' => $this->trackingUpdate->recorded_at?->toIso8601String(),
        ];
    }
}
