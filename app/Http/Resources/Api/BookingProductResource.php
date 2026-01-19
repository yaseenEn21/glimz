<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (! $this->relationLoaded('product') || ! $this->product) {
            return [];
        }

        return array_merge(
            (new ProductResource($this->product))->toArray($request),
            ['qty' => (int) $this->qty]
        );
    }
}