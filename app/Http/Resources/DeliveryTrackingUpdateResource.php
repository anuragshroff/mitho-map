<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeliveryTrackingUpdateResource extends JsonResource
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
            'order_id' => $this->order_id,
            'driver_id' => $this->driver_id,
            'latitude' => (float) $this->latitude,
            'longitude' => (float) $this->longitude,
            'heading' => $this->heading === null ? null : (float) $this->heading,
            'speed_kmh' => $this->speed_kmh === null ? null : (float) $this->speed_kmh,
            'recorded_at' => $this->recorded_at?->toIso8601String(),
        ];
    }
}
