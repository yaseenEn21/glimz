<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AutoRemindPendingBookingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct(public int $bookingId) {}

    public function handle(NotificationService $notificationService): void
    {
        $booking = Booking::query()
            ->with(['user', 'service'])
            ->find($this->bookingId);

        // لو ما لقيناه أو تغيرت حالته — لا نرسل
        if (!$booking || $booking->status !== 'pending') {
            return;
        }

        $notificationService->sendToUserUsingTemplate(
            user: $booking->user,
            templateKey: 'booking_pending_reminder',
            templateData: [
                'booking_id'   => $booking->id,
                'service_name' => is_array($booking->service->name)
                    ? ($booking->service->name['ar'] ?? $booking->service->name['en'] ?? '')
                    : $booking->service->name,
                'booking_date' => $booking->booking_date->format('Y-m-d'),
                'start_time'   => substr($booking->start_time, 0, 5),
            ],
            extraData: [
                'type'       => 'booking_reminder',
                'booking_id' => (string) $booking->id,
            ],
        );
    }
}