<?php

namespace App\Http\Resources;

use App\Models\OrderStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'subtotal_cents' => $this->subtotal_cents,
            'delivery_fee_cents' => $this->delivery_fee_cents,
            'discount_cents' => $this->discount_cents,
            'total_cents' => $this->total_cents,
            'delivery_address' => $this->delivery_address,
            'customer_notes' => $this->customer_notes,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'customer' => $this->whenLoaded('customer', function (): array {
                return [
                    'id' => $this->customer->id,
                    'name' => $this->customer->name,
                ];
            }),
            'driver' => $this->whenLoaded('driver', function (): ?array {
                if ($this->driver === null) {
                    return null;
                }

                return [
                    'id' => $this->driver->id,
                    'name' => $this->driver->name,
                ];
            }),
            'restaurant' => $this->whenLoaded('restaurant', function (): array {
                return [
                    'id' => $this->restaurant->id,
                    'name' => $this->restaurant->name,
                    'slug' => $this->restaurant->slug,
                ];
            }),
            'coupon' => $this->whenLoaded('coupon', function (): ?array {
                if ($this->coupon === null) {
                    return null;
                }

                return [
                    'id' => $this->coupon->id,
                    'code' => $this->coupon->code,
                    'title' => $this->coupon->title,
                ];
            }),
            'items' => $this->whenLoaded('items', function () {
                return $this->items->map(function ($item): array {
                    return [
                        'id' => $item->id,
                        'menu_item_id' => $item->menu_item_id,
                        'name' => $item->name,
                        'unit_price_cents' => $item->unit_price_cents,
                        'quantity' => $item->quantity,
                        'line_total_cents' => $item->line_total_cents,
                        'special_instructions' => $item->special_instructions,
                    ];
                })->all();
            }),
            'kitchen_order_ticket' => KitchenOrderTicketResource::make($this->whenLoaded('kitchenOrderTicket')),
            'tracking_updates' => DeliveryTrackingUpdateResource::collection($this->whenLoaded('trackingUpdates')),
            'status_history' => $this->whenLoaded('statusHistories', function () {
                return $this->statusHistories->map(function (OrderStatusHistory $history): array {
                    return [
                        'id' => $history->id,
                        'from_status' => $history->from_status?->value,
                        'to_status' => $history->to_status?->value,
                        'notes' => $history->notes,
                        'updated_by' => $history->updated_by,
                        'created_at' => $history->created_at?->toIso8601String(),
                    ];
                })->all();
            }),
        ];
    }
}
