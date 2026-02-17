<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StoryResource extends JsonResource
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
            'restaurant_id' => $this->restaurant_id,
            'created_by' => $this->created_by,
            'media_url' => $this->media_url,
            'caption' => $this->caption,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'is_active' => (bool) $this->is_active,
            'restaurant' => $this->whenLoaded('restaurant', function (): array {
                return [
                    'id' => $this->restaurant->id,
                    'name' => $this->restaurant->name,
                    'slug' => $this->restaurant->slug,
                ];
            }),
            'creator' => $this->whenLoaded('creator', function (): array {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
