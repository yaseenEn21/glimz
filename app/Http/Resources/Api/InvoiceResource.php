<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $paidAmount = $this->relationLoaded('payments')
            ? (float) $this->payments->where('status', 'paid')->sum('amount')
            : 0.0;

        $remaining = max(0, (float) $this->total - $paidAmount);

        $lastPayment = null;
        if ($this->relationLoaded('latestPaidPayment') && $this->latestPaidPayment) {
            $lastPayment = $this->latestPaidPayment;
        } elseif ($this->relationLoaded('latestPayment') && $this->latestPayment) {
            $lastPayment = $this->latestPayment;
        }

        // حاول تلتقط اسم الحقل عندك لأي تسمية كانت
        $paymentMethod = $lastPayment?->method
            ?? $lastPayment?->payment_method
            ?? $lastPayment?->gateway
            ?? $lastPayment?->provider
            ?? null;

        return [
            'id' => $this->id,
            'number' => $this->number,

            'type' => $this->type,
            'status' => $this->status,
            'status_label' => __('invoice_statuses.' . $this->status),
            'version' => (int) $this->version,
            'parent_invoice_id' => $this->parent_invoice_id,

            'subtotal' => (string) $this->subtotal,
            'discount' => (string) $this->discount,
            'tax' => (string) $this->tax,
            'total' => (string) $this->total,

            'paid_amount' => (string) number_format($paidAmount, 2, '.', ''),
            'remaining_amount' => (string) number_format($remaining, 2, '.', ''),

            'currency' => $this->currency,
            'issued_at' => $this->issued_at?->toDateTimeString(),
            'paid_at' => $this->paid_at?->toDateTimeString(),

            'items' => $this->items->sortBy('sort_order')
                ->map(fn ($i) => new InvoiceItemResource($i))->values(),

            'coupon' => $this->couponSnapshot(),
            'payment_method' => $paymentMethod, // string أو null
            'payment_method_label' => $paymentMethod ? __('payment_methods.' . $paymentMethod) : null,
        ];
    }

    private function couponSnapshot(): ?array
    {
        $coupon = data_get($this->meta, 'coupon');

        if (!is_array($coupon) || empty($coupon['code'] ?? null)) {
            return null;
        }

        return [
            'code' => (string) ($coupon['code'] ?? ''),

            'promotion_coupon_id' => isset($coupon['promotion_coupon_id']) ? (int) $coupon['promotion_coupon_id'] : null,
            'promotion_id' => isset($coupon['promotion_id']) ? (int) $coupon['promotion_id'] : null,

            'eligible_base' => isset($coupon['eligible_base']) ? (string) $coupon['eligible_base'] : null,
            'discount' => isset($coupon['discount']) ? (string) $coupon['discount'] : null,

            'discount_type' => $coupon['discount_type'] ?? null,   // percent/fixed
            'discount_value' => isset($coupon['discount_value']) ? (string) $coupon['discount_value'] : null,

            'applies_to' => $coupon['applies_to'] ?? null,          // both/services/packages...
            'apply_all_services' => (bool) ($coupon['apply_all_services'] ?? false),
            'apply_all_packages' => (bool) ($coupon['apply_all_packages'] ?? false),

            'applied_at' => $coupon['applied_at'] ?? null,
        ];
    }
}