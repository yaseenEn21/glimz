<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BookingPricingService
{
    public function __construct(
        private PricingService $pricingService,
        private ZoneResolverService $zoneResolver,
    ) {
    }

    public function resolve(Service $service, ?User $user, Address $address, string $time): array
    {
        $zoneId = $this->zoneResolver->resolveId((float) $address->lat, (float) $address->lng);

        $timePeriod = $this->resolveTimePeriodFromTime($time);

        $resolved = $this->pricingService->resolveServicePrice(
            $service,
            $user,
            $zoneId ?: null,
            $timePeriod
        );

        $result = [
            'zone_id' => $zoneId ?: null,
            'time_period' => in_array($timePeriod, ['morning', 'evening']) ? $timePeriod : 'all',

            'unit_price' => $resolved['unit_price'],
            'discounted_price' => $resolved['discounted_price'],
            'final_unit_price' => $resolved['final_unit_price'],

            'pricing_source' => $resolved['pricing_source'],
            'applied_id' => $resolved['applied_id'],
            'first_booking_discount' => null,
        ];

        // ✅ تحقق وطبّق خصم أول حجز
        $fbd = $this->resolveFirstBookingDiscount($service, $user, $result['final_unit_price']);
        if ($fbd) {
            $result['final_unit_price'] = $fbd['final_price'];
            $result['first_booking_discount'] = $fbd;
        }

        return $result;
    }

    private function resolveFirstBookingDiscount(Service $service, ?User $user, float $currentFinalPrice): ?array
    {
        if (!$user) {
            \Log::debug('[FBD] no user');
            return null;
        }

        $raw = DB::table('settings')
            ->where('key', 'first_booking_discount')
            ->value('value');

        if (!$raw) {
            \Log::debug('[FBD] setting not found');
            return null;
        }

        $config = json_decode($raw, true) ?? [];
        \Log::debug('[FBD] config', $config);

        if (empty($config['is_active'])) {
            \Log::debug('[FBD] not active');
            return null;
        }

        $discountType = $config['discount_type'] ?? 'percentage';
        $discountValue = (float) ($config['discount_value'] ?? 0);

        if ($discountValue <= 0) {
            \Log::debug('[FBD] discount_value <= 0');
            return null;
        }

        // ✅ Fix: cast ids to int لتفادي مشكلة string/int
        $appliesToIds = array_map('intval', $config['applies_to_service_ids'] ?? []);
        if (!empty($appliesToIds) && !in_array((int) $service->id, $appliesToIds, true)) {
            \Log::debug('[FBD] service not in list', ['service_id' => $service->id, 'list' => $appliesToIds]);
            return null;
        }

        $hasPreviousBooking = Booking::query()
            ->where('user_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->exists();

        \Log::debug('[FBD] hasPreviousBooking', ['result' => $hasPreviousBooking, 'user_id' => $user->id]);

        if ($hasPreviousBooking)
            return null;

        $finalPrice = match ($discountType) {
            'percentage' => max(0.0, $currentFinalPrice * (1 - $discountValue / 100)),
            'fixed' => max(0.0, $currentFinalPrice - $discountValue),
            'special_price' => max(0.0, (float) $discountValue),
            default => $currentFinalPrice,
        };

        $discountAmount = round($currentFinalPrice - $finalPrice, 2);

        if ($discountAmount <= 0) {
            \Log::debug('[FBD] discountAmount <= 0');
            return null;
        }

        \Log::debug('[FBD] ✅ discount applied', [
            'type' => $discountType,
            'value' => $discountValue,
            'before' => $currentFinalPrice,
            'after' => $finalPrice,
            'amount' => $discountAmount,
        ]);

        return [
            'applied' => true,
            'type' => $discountType,
            'value' => $discountValue,
            'amount' => $discountAmount,
            'price_before' => $currentFinalPrice,
            'final_price' => round($finalPrice, 2),
        ];
    }

    private function resolveTimePeriodFromTime(string $time): string
    {
        try {
            $h = (int) explode(':', $time)[0];
            return $h < 15 ? 'morning' : 'evening';
        } catch (\Throwable $e) {
            return 'all';
        }
    }
}