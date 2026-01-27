<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Jobs\SyncBookingToRekazJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncBookingsToRekaz extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'rekaz:sync-bookings 
                            {--booking-id= : Sync specific booking by ID}
                            {--status= : Sync bookings with specific status}
                            {--from-date= : Sync bookings from this date (Y-m-d)}
                            {--to-date= : Sync bookings to this date (Y-m-d)}
                            {--failed-only : Sync only previously failed bookings}
                            {--force : Force sync even if already synced}
                            {--action=create : Action to perform (create|update)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync bookings to Rekaz platform';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Rekaz sync...');

        // Build query
        $query = Booking::query()
            ->with(['user', 'service', 'address', 'car']);

        // Filter by booking ID
        if ($bookingId = $this->option('booking-id')) {
            $query->where('id', $bookingId);
        }

        // Filter by status
        if ($status = $this->option('status')) {
            $query->where('status', $status);
        }

        // Filter by date range
        if ($fromDate = $this->option('from-date')) {
            $query->whereDate('booking_date', '>=', $fromDate);
        }

        if ($toDate = $this->option('to-date')) {
            $query->whereDate('booking_date', '<=', $toDate);
        }

        // Filter failed only
        if ($this->option('failed-only')) {
            $query->where(function ($q) {
                $q->whereJsonContains('meta->rekaz_sync_failed', true)
                  ->orWhereJsonContains('meta->rekaz_synced', false)
                  ->orWhereNull('meta->rekaz_booking_id');
            });
        }

        // Exclude already synced if not forcing
        if (!$this->option('force') && !$this->option('failed-only')) {
            $query->where(function ($q) {
                $q->whereJsonContains('meta->rekaz_synced', false)
                  ->orWhereNull('meta->rekaz_booking_id')
                  ->orWhereJsonContains('meta->rekaz_sync_failed', true);
            });
        }

        $bookings = $query->get();

        if ($bookings->isEmpty()) {
            $this->warn('No bookings found matching the criteria.');
            return Command::SUCCESS;
        }

        $this->info("Found {$bookings->count()} booking(s) to sync.");

        $bar = $this->output->createProgressBar($bookings->count());
        $bar->start();

        $action = $this->option('action');
        $synced = 0;
        $failed = 0;

        foreach ($bookings as $booking) {
            try {
                SyncBookingToRekazJob::dispatch($booking, $action)
                    ->onQueue('rekaz-sync');

                $synced++;
            } catch (\Exception $e) {
                $this->error("\nFailed to dispatch sync job for booking #{$booking->id}: {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("✓ Dispatched {$synced} sync job(s)");
        if ($failed > 0) {
            $this->error("✗ Failed to dispatch {$failed} job(s)");
        }

        $this->info('Jobs have been queued. Monitor queue workers for actual sync status.');

        return Command::SUCCESS;
    }
}