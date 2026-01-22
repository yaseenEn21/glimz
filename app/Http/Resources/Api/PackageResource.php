<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Controller سيحمّل subscriptions للمستخدم الحالي فقط (active) إن كان مسجل
        $sub = $this->relationLoaded('subscriptions') ? $this->subscriptions->first() : null;
        $randomPattern = 'package-pattren-' . rand(1, 3) . '.png';
        $randomIcon = 'card-icon-' . rand(1, 3) . '.png';

        return [
            'id' => $this->id,
            'name' => i18n($this->name),
            'description' => i18n($this->description),

            'short_description' => i18n($this->description),
            'label' => i18n($this->label),

            'price' => (string) $this->price,
            'discounted_price' => $this->discounted_price !== null ? (string) $this->discounted_price : null,

            'washes_count' => $this->washes_count,

            'remaining_washes' => $sub?->remaining_washes,
            'total_washes_snapshot' => $sub?->total_washes_snapshot,

            'validity_days' => $this->validity_days,

            'pattren_url' => $this->getFirstMediaUrl('cover_image') ?: defaultImage($randomPattern),
            'image_url' => $this->getFirstMediaUrl('image') ?: defaultImage($randomIcon),

            // مهم للـ Home: هل هو مشترك وفعّال؟
            'is_subscribed_active' => $sub ? (bool) $sub->is_currently_active : false,
            'subscription_ends_at' => $sub?->ends_at?->toISOString(),

            // تفاصيل الخدمات داخل الباقة (فقط عند show أو with_services=1)
            // 'services' => $this->whenLoaded('services', function () use ($request) {
            //     return PackageIncludedServiceResource::collection($this->services);
            // }),
            'service' => $this->whenLoaded('services', function () {
                $first = $this->services->first();
                return $first ? new PackageIncludedServiceResource($first) : null;
            }),

        ];
    }
}
