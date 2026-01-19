<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageIncludedServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $price = (float) $this->price;
        $discounted = $this->discounted_price !== null ? (float) $this->discounted_price : null;

        return [
            'id' => $this->id,
            'name' => i18n($this->name),
            'description' => i18n($this->description),
            'price' => number_format($price, 2, '.', ''),
            'discounted_price' => $discounted !== null ? number_format($discounted, 2, '.', '') : null,
            'rate' => $this->rating_count > 0
                ? number_format((float) $this->rating_avg, 1, '.', '')
                : null,
            'rate_count' => (int) $this->rating_count,
            'duration_minutes' => $this->duration_minutes,
            'image_url' => $this->getImageUrl(app()->getLocale()) ?: defaultImage(),
        ];
    }
}
