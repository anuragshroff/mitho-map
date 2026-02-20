<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderConversationType;
use App\Events\OrderChatMessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreOrderChatMessageRequest;
use App\Models\Order;
use App\Models\OrderConversation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderChatController extends Controller
{
    public function index(Order $order, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        /**
         * @var array{conversation_type: string} $validated
         */
        $validated = $request->validate([
            'conversation_type' => [
                'required',
                Rule::in(OrderConversation::conversationTypeValues()),
            ],
        ]);

        $conversationType = OrderConversationType::from($validated['conversation_type']);

        $this->ensureUserCanAccessOrderConversation($order, $user, $conversationType);

        $conversation = $this->getOrCreateConversation($order, $user, $conversationType);

        $messages = $conversation->messages()
            ->with(['sender:id,name,role', 'conversation:id,conversation_type'])
            ->latest('id')
            ->limit(50)
            ->get()
            ->reverse()
            ->values()
            ->map(function ($message): array {
                return [
                    'id' => $message->id,
                    'order_id' => $message->order_id,
                    'conversation_type' => $message->conversation?->conversation_type?->value,
                    'message' => $message->message,
                    'sent_at' => $message->sent_at?->toIso8601String(),
                    'sender' => [
                        'id' => $message->sender?->id,
                        'name' => $message->sender?->name,
                        'role' => $message->sender?->role?->value,
                    ],
                ];
            })
            ->all();

        return response()->json([
            'conversation_id' => $conversation->id,
            'order_id' => $order->id,
            'conversation_type' => $conversation->conversation_type?->value,
            'messages' => $messages,
        ]);
    }

    public function store(
        Order $order,
        StoreOrderChatMessageRequest $request,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();
        $conversationType = OrderConversationType::from($validated['conversation_type']);

        $this->ensureUserCanAccessOrderConversation($order, $user, $conversationType);

        $conversation = $this->getOrCreateConversation($order, $user, $conversationType);
        $messageBody = trim($validated['message']);

        if ($messageBody === '') {
            throw ValidationException::withMessages([
                'message' => 'Message cannot be empty.',
            ]);
        }

        $message = $conversation->messages()->create([
            'order_id' => $order->id,
            'sender_id' => $user->id,
            'message' => $messageBody,
            'sent_at' => now(),
        ]);

        $conversation->forceFill([
            'last_message_at' => $message->sent_at,
        ])->save();

        $message->load(['sender:id,name,role', 'conversation']);

        OrderChatMessageSent::dispatch($message);

        return response()->json([
            'id' => $message->id,
            'order_id' => $message->order_id,
            'conversation_type' => $conversationType->value,
            'message' => $message->message,
            'sent_at' => $message->sent_at?->toIso8601String(),
            'sender' => [
                'id' => $message->sender?->id,
                'name' => $message->sender?->name,
                'role' => $message->sender?->role?->value,
            ],
        ], 201);
    }

    /**
     * @throws AuthorizationException
     * @throws ValidationException
     */
    protected function ensureUserCanAccessOrderConversation(
        Order $order,
        User $user,
        OrderConversationType $conversationType,
    ): void {
        if (
            in_array($conversationType, [OrderConversationType::UserDriver, OrderConversationType::AdminDriver], true)
            && $order->driver_id === null
        ) {
            throw ValidationException::withMessages([
                'conversation_type' => 'Driver conversation is not available until a driver is assigned.',
            ]);
        }

        $authorized = match ($conversationType) {
            OrderConversationType::UserDriver => $order->customer_id === $user->id || $order->driver_id === $user->id,
            OrderConversationType::UserAdmin => $order->customer_id === $user->id || $user->role === \App\Enums\UserRole::Admin,
            OrderConversationType::AdminDriver => $order->driver_id === $user->id || $user->role === \App\Enums\UserRole::Admin,
        };

        if ($authorized) {
            return;
        }

        throw new AuthorizationException('You are not allowed to access this order conversation.');
    }

    protected function getOrCreateConversation(
        Order $order,
        User $user,
        OrderConversationType $conversationType,
    ): OrderConversation {
        return OrderConversation::query()->firstOrCreate(
            [
                'order_id' => $order->id,
                'conversation_type' => $conversationType,
            ],
            [
                'created_by' => $user->id,
            ],
        );
    }
}
