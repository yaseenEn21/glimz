<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Jobs\AutoCancelPendingBookingJob;
use Illuminate\Console\Command;

class CancelStalePendingBookings extends Command
{
    protected $signature = 'bookings:cancel-stale-pending';
    protected $description = 'Cancel pending bookings that exceeded the payment window';

    public function handle()
    {
        $minutes = (int) config('booking.pending_auto_cancel_minutes', 10);

        $bookings = Booking::query()
            ->where('status', 'pending')
            ->where('created_at', '<=', now()->subMinutes($minutes))
            ->get();

        foreach ($bookings as $booking) {
            AutoCancelPendingBookingJob::dispatch($booking->id);
            $this->info("Dispatched cancel for booking: {$booking->id}");
        }

        $this->info("Total: {$bookings->count()} bookings");
    }
}