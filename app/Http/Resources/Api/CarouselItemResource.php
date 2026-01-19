<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CarouselItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'label' => i18n($this->label),
            'title' => i18n($this->title),
            'description' => i18n($this->description),
            'hint' => i18n($this->hint),
            'cta' => i18n($this->cta),

            'image_url' => $this->getImageUrl(app()->getLocale()),

            'target' => $this->carouselable_type && $this->carouselable_id ? [
                'type' => $this->carouselable_type,
                'id' => (int) $this->carouselable_id,
            ] : null,
        ];
    }
}