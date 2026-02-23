<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Models\Partner;
use App\Services\MismarWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendPartnerWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Booking $booking
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $partner = $this->booking->partner;

        if (!$partner) {
            Log::info('Partner webhook skipped - no partner', [
                'booking_id' => $this->booking->id,
            ]);
            return;
        }

        if (!$partner->is_active) {
            Log::info('Partner webhook skipped - partner inactive', [
                'booking_id' => $this->booking->id,
                'partner_id' => $partner->id,
            ]);
            return;
        }

        // ✅ Dispatch حسب نوع الشريك
        $success = match ($partner->webhook_type ?? 'generic') {
            'mismar' => $this->sendMismarWebhook($partner),
            'generic' => $this->sendGenericWebhook($partner),
            'helloapp' => $this->sendHelloAppWebhook($partner),
            default => $this->sendGenericWebhook($partner),
        };

        if (!$success) {
            throw new \Exception('Partner webhook failed');
        }
    }

    /**
     * إرسال webhook لمسمار
     */
    protected function sendMismarWebhook(Partner $partner): bool
    {
        $service = app(MismarWebhookService::class);
        return $service->sendStatusUpdate($partner, $this->booking);
    }

    /**
     * إرسال webhook عام (POST JSON)
     */
    protected function sendGenericWebhook(Partner $partner): bool
    {
        if (!$partner->webhook_url) {
            return false;
        }

        $payload = [
            'event' => 'booking.status_changed',
            'booking' => [
                'id' => $this->booking->id,
                'external_id' => $this->booking->external_id,
                'status' => $this->booking->status,
                'booking_date' => $this->booking->booking_date->format('Y-m-d'),
                'start_time' => substr($this->booking->start_time, 0, 5),
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        try {
            $response = Http::timeout(10)
                ->post($partner->webhook_url, $payload);

            if ($response->successful()) {
                Log::info('Generic partner webhook sent', [
                    'partner_id' => $partner->id,
                    'booking_id' => $this->booking->id,
                ]);
                return true;
            }

            Log::error('Generic partner webhook failed', [
                'partner_id' => $partner->id,
                'booking_id' => $this->booking->id,
                'status_code' => $response->status(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Generic partner webhook exception', [
                'partner_id' => $partner->id,
                'booking_id' => $this->booking->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function sendHelloAppWebhook(Partner $partner): bool
    {
        if (!$partner->webhook_url || !$partner->external_token) {
            return false;
        }

        $url = rtrim($partner->webhook_url, '/') 
         . "/api/v1/{$partner->username}/bookings/{$this->booking->external_id}/status";

        $payload = [
            'event' => 'booking.status_changed',
            'booking' => [
                'external_id' => $this->booking->external_id,
                'status' => $this->booking->status,
                'booking_date' => $this->booking->booking_date->format('Y-m-d'),
                'start_time' => substr($this->booking->start_time, 0, 5),
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        $response = Http::timeout(10)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $partner->external_token,
                'X-Correlation-Id' => (string) \Str::uuid(),
            ])
            ->post($url, $payload);

        return $response->successful();
    }
}