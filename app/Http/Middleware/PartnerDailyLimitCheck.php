<?php

namespace App\Http\Middleware;

use App\Models\Booking;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PartnerDailyLimitCheck
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $partner = $request->input('partner'); // من PartnerApiAuth

        if (!$partner) {
            return response()->json([
                'success' => false,
                'error' => 'Partner not authenticated',
            ], 401);
        }

        // استخدام Cache للأداء (TTL: نهاية اليوم)
        $cacheKey = "partner_{$partner->id}_daily_bookings_" . now()->format('Y-m-d');
        $cacheTTL = now()->endOfDay();

        $todayCount = Cache::remember($cacheKey, $cacheTTL, function () use ($partner) {
            return Booking::query()
                ->where('partner_id', $partner->id)
                ->whereDate('created_at', now()->toDateString())
                ->count();
        });

        if ($todayCount >= $partner->daily_booking_limit) {
            return response()->json([
                'success' => false,
                'error' => 'Daily booking limit reached',
                'error_code' => 'DAILY_LIMIT_EXCEEDED',
                'data' => [
                    'limit' => $partner->daily_booking_limit,
                    'used' => $todayCount,
                ],
            ], 429);
        }

        return $next($request);
    }
}