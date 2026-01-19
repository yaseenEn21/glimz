<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\BookingCancellationService;
use App\Services\BookingService;
use App\Services\InvoiceService;
use App\Services\WalletService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AutoCancelPendingBookingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $bookingId)
    {
    }

    public function handle(
        BookingCancellationService $cancelService,
        WalletService $walletService,
        InvoiceService $invoiceService
    ) {
        $booking = Booking::query()->find($this->bookingId);
        if (!$booking)
            return;

        if ($booking->status !== 'pending')
            return;

        $systemActorId = (int) (config('app.system_user_id') ?? 1);

        $cancelService->cancel(
            $booking,
            'auto_cancel_unpaid',
            null,
            $systemActorId,
            $walletService,
            $invoiceService
        );
    }

}