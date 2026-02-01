<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Partner;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MismarWebhookService
{
    /**
     * إرسال تحديث حالة إلى Mismar API
     */
    public function sendStatusUpdate(Partner $partner, Booking $booking): bool
    {
        if (!$partner->webhook_url) {
            Log::warning('Mismar webhook skipped - no webhook URL', [
                'partner_id' => $partner->id,
                'booking_id' => $booking->id,
            ]);
            return false;
        }

        // 1. Map status to Mismar internalStatus
        $internalStatus = $this->mapStatusToMismar($booking->status);

        if ($internalStatus === null) {
            Log::info('Mismar webhook skipped - status not mapped', [
                'partner_id' => $partner->id,
                'booking_id' => $booking->id,
                'status' => $booking->status,
            ]);
            return false;
        }

        // 2. Build URL
        $url = str_replace(
            '{orderId}',
            $booking->external_id,
            'https://api.mismarapp.com/operationApi/v1/orders/{orderId}/carCareInternalStatus'
        );

        // 3. Prepare payload
        $payload = [
            'internalStatus' => $internalStatus,
        ];

        try {
            Log::info('Sending Mismar webhook', [
                'partner_id' => $partner->id,
                'booking_id' => $booking->id,
                'external_id' => $booking->external_id,
                'url' => $url,
                'status' => $booking->status,
                'internalStatus' => $internalStatus,
            ]);

            // 4. Send request
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-auth-token' => $partner->api_token, // استخدام token الشريك
                    'Content-Type' => 'application/json',
                ])
                ->put($url, $payload);

            // 5. Handle response
            if ($response->successful()) {
                $data = $response->json();

                Log::info('Mismar webhook sent successfully', [
                    'partner_id' => $partner->id,
                    'booking_id' => $booking->id,
                    'external_id' => $booking->external_id,
                    'status_code' => $response->status(),
                    'response' => $data,
                ]);

                return true;
            } else {
                Log::error('Mismar webhook failed', [
                    'partner_id' => $partner->id,
                    'booking_id' => $booking->id,
                    'external_id' => $booking->external_id,
                    'status_code' => $response->status(),
                    'response' => $response->body(),
                ]);

                return false;
            }
        } catch (\Exception $e) {
            Log::error('Mismar webhook exception', [
                'partner_id' => $partner->id,
                'booking_id' => $booking->id,
                'external_id' => $booking->external_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Map internal status to Mismar status codes
     */
    protected function mapStatusToMismar(string $status): ?int
    {
        return match ($status) {
            'moving' => 23,      // الفني بالطريق إليك
            'arrived' => 12,     // جاري العمل
            'completed' => 19,   // تم
            default => null,     // لا نرسل للحالات الأخرى
        };
    }
}