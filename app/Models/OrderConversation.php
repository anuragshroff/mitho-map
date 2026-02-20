<?php

namespace App\Models;

use App\Enums\OrderConversationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderConversation extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'conversation_type',
        'created_by',
        'last_message_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'conversation_type' => OrderConversationType::class,
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function conversationTypeValues(): array
    {
        return array_map(
            static fn (OrderConversationType $type): string => $type->value,
            OrderConversationType::cases(),
        );
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(OrderChatMessage::class)->orderBy('id');
    }
}
