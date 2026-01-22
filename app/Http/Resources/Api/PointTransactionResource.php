<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PointTransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => __('points.types.' . $this->type),
            'points' => (int) $this->points,

            'money_amount' => $this->money_amount !== null ? (string) $this->money_amount : null,
            'currency' => $this->currency,

            'note' => $this->note,
            'created_at' => $this->created_at?->toDateTimeString(),
        ];
    }
}
