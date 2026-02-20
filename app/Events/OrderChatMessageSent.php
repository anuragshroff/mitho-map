<?php

namespace App\Events;

use App\Models\OrderChatMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public OrderChatMessage $message) {}

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel(
                'orders.'.$this->message->order_id.'.conversation.'.$this->message->conversation?->conversation_type?->value
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'order.chat.message.sent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'order_id' => $this->message->order_id,
            'conversation_type' => $this->message->conversation?->conversation_type?->value,
            'sender_id' => $this->message->sender_id,
            'message' => $this->message->message,
            'sent_at' => $this->message->sent_at?->toIso8601String(),
        ];
    }
}
