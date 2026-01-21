<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageSubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'is_currently_active' => (bool) $this->is_currently_active,
            'starts_at' => $this->starts_at?->toISOString(),
            'ends_at' => $this->ends_at?->toISOString(),

            'price_snapshot' => (string) $this->price_snapshot,
            'discounted_price_snapshot' => $this->discounted_price_snapshot !== null ? (string) $this->discounted_price_snapshot : null,
            'final_price_snapshot' => (string) $this->final_price_snapshot,

            'total_washes_snapshot' => (int) $this->total_washes_snapshot,
            'used_washes' => (int) $this->total_washes_snapshot - (int) $this->remaining_washes,
            'remaining_washes' => (int) $this->remaining_washes,
            'usage_percentage' => $this->total_washes_snapshot > 0
                ? round((($this->total_washes_snapshot - $this->remaining_washes) / $this->total_washes_snapshot) * 100, 2)
                : 0,
                
            'package' => $this->whenLoaded('package', fn() => new PackageResource($this->package)),
        ];
    }
}