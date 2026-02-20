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
            'tracking_channel' => 'orders.'.$this->id,
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
            'kitchen_state' => $this->whenLoaded('kitchenOrderTicket', function (): ?array {
                if ($this->kitchenOrderTicket === null) {
                    return null;
                }

                return [
                    'status' => $this->kitchenOrderTicket->status?->value,
                    'accepted_at' => $this->kitchenOrderTicket->accepted_at?->toIso8601String(),
                    'ready_at' => $this->kitchenOrderTicket->ready_at?->toIso8601String(),
                ];
            }),
            'tracking_updates' => DeliveryTrackingUpdateResource::collection($this->whenLoaded('trackingUpdates')),
            'latest_tracking_update' => $this->whenLoaded('trackingUpdates', function (): ?array {
                $latest = $this->trackingUpdates->first();

                if ($latest === null) {
                    return null;
                }

                return [
                    'id' => $latest->id,
                    'order_id' => $latest->order_id,
                    'driver_id' => $latest->driver_id,
                    'latitude' => (float) $latest->latitude,
                    'longitude' => (float) $latest->longitude,
                    'heading' => $latest->heading === null ? null : (float) $latest->heading,
                    'speed_kmh' => $latest->speed_kmh === null ? null : (float) $latest->speed_kmh,
                    'recorded_at' => $latest->recorded_at?->toIso8601String(),
                ];
            }),
            'live_state' => [
                'is_preparing' => in_array($this->status?->value, ['confirmed', 'preparing'], true),
                'is_out_for_delivery' => $this->status?->value === 'out_for_delivery',
                'is_delivered' => $this->status?->value === 'delivered',
                'is_cancelled' => $this->status?->value === 'cancelled',
                'has_driver' => $this->driver_id !== null,
            ],
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
