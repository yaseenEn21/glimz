<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\RekazService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RekazWebhookController extends Controller
{
    protected RekazService $rekazService;

    public function __construct(RekazService $rekazService)
    {
        $this->rekazService = $rekazService;
    }

    /**
     * معالجة webhook من ركاز
     */
    public function handle(Request $request)
    {
        // التحقق من صحة الـ webhook
        if (!$this->verifyWebhookSignature($request)) {
            Log::warning('Rekaz webhook signature verification failed', [
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $payload = $request->all();
        $eventType = $payload['event'] ?? null;

        Log::info('Rekaz webhook received', [
            'event' => $eventType,
            'payload' => $payload,
        ]);

        try {
            match($eventType) {
                'booking.created' => $this->handleBookingCreated($payload),
                'booking.updated' => $this->handleBookingUpdated($payload),
                'booking.cancelled' => $this->handleBookingCancelled($payload),
                'booking.completed' => $this->handleBookingCompleted($payload),
                'booking.deleted' => $this->handleBookingDeleted($payload),
                default => $this->handleUnknownEvent($eventType, $payload),
            };

            return response()->json(['success' => true], 200);

        } catch (\Exception $e) {
            Log::error('Rekaz webhook processing failed', [
                'event' => $eventType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Processing failed'
            ], 500);
        }
    }

    /**
     * التحقق من توقيع الـ webhook
     */
    protected function verifyWebhookSignature(Request $request): bool
    {
        $secret = config('services.rekaz.webhook.secret');

        // إذا لم يكن هناك secret معرف، نقبل جميع الطلبات (غير آمن - للتطوير فقط)
        if (empty($secret)) {
            Log::warning('Rekaz webhook secret not configured - accepting all requests');
            return true;
        }

        $signature = $request->header('X-Rekaz-Signature');
        if (!$signature) {
            return false;
        }

        // حساب التوقيع المتوقع
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * معالجة حدث إنشاء حجز
     */
    protected function handleBookingCreated(array $payload): void
    {
        $data = $payload['data'] ?? [];
        $externalId = $data['external_id'] ?? null;
        $rekazBookingId = $data['id'] ?? null;

        if (!$externalId || !$rekazBookingId) {
            Log::warning('Rekaz webhook missing required fields', [
                'event' => 'booking.created',
                'data' => $data,
            ]);
            return;
        }

        // تحديث الحجز بـ ID ركاز
        $booking = Booking::find($externalId);
        if (!$booking) {
            Log::warning('Rekaz webhook: booking not found', [
                'external_id' => $externalId,
                'rekaz_id' => $rekazBookingId,
            ]);
            return;
        }

        $meta = $booking->meta ?? [];
        $meta['rekaz_booking_id'] = $rekazBookingId;
        $meta['rekaz_synced'] = true;
        $meta['rekaz_webhook_received'] = now()->toIso8601String();

        $booking->update(['meta' => $meta]);

        Log::info('Booking updated from Rekaz webhook', [
            'booking_id' => $booking->id,
            'rekaz_id' => $rekazBookingId,
        ]);
    }

    /**
     * معالجة حدث تحديث حجز
     */
    protected function handleBookingUpdated(array $payload): void
    {
        $data = $payload['data'] ?? [];
        $externalId = $data['external_id'] ?? null;
        $rekazStatus = $data['status'] ?? null;

        if (!$externalId) {
            return;
        }

        $booking = Booking::find($externalId);
        if (!$booking) {
            return;
        }

        // تحديث الحالة إذا اختلفت
        if ($rekazStatus) {
            $localStatus = $this->rekazService->mapStatusFromRekaz($rekazStatus);
            if ($booking->status !== $localStatus) {
                $booking->update([
                    'status' => $localStatus,
                    'updated_at' => now(),
                ]);

                Log::info('Booking status updated from Rekaz webhook', [
                    'booking_id' => $booking->id,
                    'old_status' => $booking->status,
                    'new_status' => $localStatus,
                ]);
            }
        }
    }

    /**
     * معالجة حدث إلغاء حجز
     */
    protected function handleBookingCancelled(array $payload): void
    {
        $data = $payload['data'] ?? [];
        $externalId = $data['external_id'] ?? null;
        $reason = $data['cancel_reason'] ?? null;

        if (!$externalId) {
            return;
        }

        $booking = Booking::find($externalId);
        if (!$booking) {
            return;
        }

        // إلغاء الحجز إذا لم يكن ملغي بالفعل
        if ($booking->status !== 'cancelled') {
            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancel_reason' => $reason ?? 'Cancelled from Rekaz',
            ]);

            Log::info('Booking cancelled from Rekaz webhook', [
                'booking_id' => $booking->id,
                'reason' => $reason,
            ]);
        }
    }

    /**
     * معالجة حدث إتمام حجز
     */
    protected function handleBookingCompleted(array $payload): void
    {
        $data = $payload['data'] ?? [];
        $externalId = $data['external_id'] ?? null;

        if (!$externalId) {
            return;
        }

        $booking = Booking::find($externalId);
        if (!$booking) {
            return;
        }

        // تحديث الحجز لـ completed
        if ($booking->status !== 'completed') {
            $booking->update([
                'status' => 'completed',
            ]);

            Log::info('Booking completed from Rekaz webhook', [
                'booking_id' => $booking->id,
            ]);
        }
    }

    /**
     * معالجة حدث حذف حجز
     */
    protected function handleBookingDeleted(array $payload): void
    {
        $data = $payload['data'] ?? [];
        $externalId = $data['external_id'] ?? null;

        if (!$externalId) {
            return;
        }

        Log::info('Booking deleted notification from Rekaz', [
            'external_id' => $externalId,
        ]);

        // لا نحذف الحجز من نظامنا، فقط نسجل الحدث
        // يمكنك تغيير هذا السلوك حسب احتياجاتك
    }

    /**
     * معالجة حدث غير معروف
     */
    protected function handleUnknownEvent(?string $eventType, array $payload): void
    {
        Log::warning('Rekaz webhook: unknown event type', [
            'event' => $eventType,
            'payload' => $payload,
        ]);
    }
}