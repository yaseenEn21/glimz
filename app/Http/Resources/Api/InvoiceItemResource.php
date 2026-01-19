<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $imageUrl = null;

        if ($this->relationLoaded('itemable') && $this->itemable) {

            $locale = str_starts_with(app()->getLocale(), 'en') ? 'en' : 'ar';

            if (method_exists($this->itemable, 'getImageUrl')) {
                $imageUrl = $this->itemable->getImageUrl($locale);
            }

            if (!$imageUrl && method_exists($this->itemable, 'getFirstMediaUrl')) {
                $imageUrl =
                    $this->itemable->getFirstMediaUrl("image_{$locale}")
                    ?: $this->itemable->getFirstMediaUrl('image_ar')
                    ?: $this->itemable->getFirstMediaUrl('image_en')
                    ?: $this->itemable->getFirstMediaUrl('image')
                    ?: null;
            }
        }

        return [
            'id' => (int) $this->id,
            'item_type' => (string) $this->item_type,

            'title' => $this->title ? i18n($this->title) : null,
            'description' => $this->description ? i18n($this->description) : null,

            'qty' => (string) $this->qty,
            'unit_price' => (string) $this->unit_price,
            'line_tax' => (string) $this->line_tax,
            'line_total' => (string) $this->line_total,

            'image_url' => $imageUrl ?: defaultImage(),
        ];
    }
}