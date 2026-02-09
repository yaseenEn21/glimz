<?php

namespace App\Http\Resources\Api;

use App\Support\BookingStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $latestUnpaid = $this->relationLoaded('invoices')
            ? $this->invoices->where('status', 'unpaid')->sortByDesc('id')->first()
            : null;

        $service = $this->relationLoaded('service') ? $this->service : null;

        return [
            'id' => (int) $this->id,
            // 'status' => (string) $this->status,
            // 'status_label' => __('bookings.status.' . $this->status),
            'status_meta' => BookingStatus::meta((string) $this->status),

            'date' => $this->booking_date?->format('d-m-Y'),
            'datetime' => $this->booking_date && $this->start_time
                ? \Carbon\Carbon::parse($this->booking_date->format('Y-m-d') . ' ' . $this->start_time)
                    ->toISOString()
                : null,
            'start_time' => substr((string) $this->start_time, 0, 5),
            'end_time' => substr((string) $this->end_time, 0, 5),
            'duration_minutes' => (int) $this->duration_minutes,

            'car_id' => (int) $this->car_id,
            'car' => new CarResource($this->whenLoaded('car')),
            'address_id' => (int) $this->address_id,
            'address_label' => $this->address?->address_line,
            // 'service_id' => (int) $this->service_id,
            'employee_id' => $this->employee_id ? (int) $this->employee_id : null,

            // 'service_name' => $this->relationLoaded('service') ? i18n($this->service->name) : null,

            // 'service' => [
            //     'id' => $service->id,
            //     'name' => i18n($service->name),
            //     'image_url' => $service->getImageUrl(app()->getLocale()) ?: defaultImage(),
            //     'price' => (float) $this->service_final_price_snapshot,
            // ],

            'service' => new ServiceResource($this->whenLoaded('service')),

            'package_subscription_id' => $this->package_subscription_id ? (int) $this->package_subscription_id : null,
            'package_covers_service' => (bool) ($this->meta['package_covers_service'] ?? false),
            'package_deducted' => (bool) ($this->meta['package_deducted'] ?? false),

            'totals' => [
                'service_final' => (float) $this->service_final_price_snapshot,
                'products_subtotal' => (float) $this->products_subtotal_snapshot,
                'subtotal' => (float) $this->subtotal_snapshot,
                'discount' => (float) $this->discount_snapshot,
                'tax' => (float) $this->tax_snapshot,
                'total' => (float) $this->total_snapshot,
                'currency' => (string) $this->currency,
            ],

            'products' => BookingProductResource::collection($this->whenLoaded('products')),

            'latest_unpaid_invoice' => new InvoiceResource($latestUnpaid),

            // 'latest_unpaid_invoice' => $latestUnpaid ? [
            //     'id' => (int) $latestUnpaid->id,
            //     'number' => (string) $latestUnpaid->number,
            //     'status' => (string) $latestUnpaid->status,
            //     'total' => (float) $latestUnpaid->total,
            //     'currency' => (string) $latestUnpaid->currency,
            // ] : null,

            'rating' => $this->rating ? (int) $this->rating : null,
            'rating_comment' => $this->rating_comment,
            'rated_at' => $this->rated_at?->toISOString(),
        ];
    }
}
