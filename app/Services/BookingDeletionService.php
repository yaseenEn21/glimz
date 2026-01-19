<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\DB;

class BookingDeletionService
{
    public function deleteLikeCancel(
        Booking $booking,
        string $reason,
        ?string $note,
        int $actorId,
        WalletService $walletService,
        InvoiceService $invoiceService,
        BookingCancellationService $cancellationService
    ): void {
        
        $cancellationService->cancel(
            booking: $booking,
            reason: $reason,
            note: $note,
            actorId: $actorId,
            walletService: $walletService,
            invoiceService: $invoiceService
        );

        DB::transaction(function () use ($booking) {
            $b = Booking::query()
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->first();

            if (! $b) return;

            if (method_exists($b, 'trashed') && $b->trashed()) return;

            if (method_exists($b, 'deleteQuietly')) {
                $b->deleteQuietly();
            } else {
                $b->delete();
            }
        });
    }
}