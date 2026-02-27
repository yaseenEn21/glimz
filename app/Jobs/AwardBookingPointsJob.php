<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\Setting;
use App\Services\PointsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AwardBookingPointsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $bookingId,
        public ?int $actorId = null
    ) {}

    public function handle(PointsService $pointsService): void
    {
        // 1) التحقق من إعداد النقاط التلقائية
        $autoAward = (bool) Setting::getValue('points.auto_award_booking_points', true);

        if (!$autoAward) {
            Log::info('AwardBookingPointsJob: skipped — auto award is disabled', [
                'booking_id' => $this->bookingId,
            ]);
            return;
        }

        // 2) جلب الحجز
        $booking = Booking::query()
            ->with([
                'user:id,is_active',
                'service:id,points',
                'partner:id,allow_customer_points',
            ])
            ->find($this->bookingId);

        if (!$booking) {
            Log::warning('AwardBookingPointsJob: booking not found', [
                'booking_id' => $this->bookingId,
            ]);
            return;
        }

        // 3) التحقق: هل الحجز تابع لشريك؟
        if ($booking->partner_id && $booking->partner) {
            if (!$booking->partner->allow_customer_points) {
                Log::info('AwardBookingPointsJob: skipped — partner does not allow points', [
                    'booking_id' => $this->bookingId,
                    'partner_id' => $booking->partner_id,
                    'partner_name' => $booking->partner->name,
                ]);
                return;
            }

            Log::info('AwardBookingPointsJob: partner allows points, proceeding', [
                'booking_id' => $this->bookingId,
                'partner_id' => $booking->partner_id,
            ]);
        }

        // 4) منح النقاط
        $pointsService->awardCompletedBookingPoints($booking, $this->actorId);

        Log::info('AwardBookingPointsJob: points awarded', [
            'booking_id' => $this->bookingId,
            'user_id'    => $booking->user_id,
        ]);
    }
}