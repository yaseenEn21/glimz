<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\PointsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AwardBookingPointsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $bookingId,
        public ?int $actorId = null
    ) {}

    public function handle(PointsService $pointsService): void
    {
        $booking = Booking::query()
            ->with(['user:id,is_active', 'service:id,points'])
            ->find($this->bookingId);

        if (! $booking) return;

        $pointsService->awardCompletedBookingPoints($booking, $this->actorId);
    }
}