<?php

namespace App\Jobs;

use App\Models\Booking;
use App\Services\RekazService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncBookingToRekazJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * عدد المحاولات
     */
    public int $tries = 3;

    /**
     * الوقت بالثواني قبل timeout
     */
    public int $timeout = 60;

    /**
     * الوقت بين المحاولات (بالثواني)
     */
    public int $backoff = 10;

    protected Booking $booking;
    protected string $action; // 'create', 'update', 'cancel', 'delete'
    protected ?array $updateData;

    // public string $queue = 'rekaz-sync';


    /**
     * Create a new job instance.
     */
    public function __construct(
        Booking $booking,
        string $action = 'create',
        ?array $updateData = null
    ) {
        $this->booking = $booking;
        $this->action = $action;
        $this->updateData = $updateData;
    }

    /**
     * Execute the job.
     */
    public function handle(RekazService $rekazService): void
    {
        try {
            Log::info("Rekaz sync job started", [
                'booking_id' => $this->booking->id,
                'action' => $this->action,
            ]);

            switch ($this->action) {
                case 'create':
                    $result = $this->handleCreate($rekazService);
                    break;

                case 'update':
                    $result = $this->handleUpdate($rekazService);
                    break;

                case 'cancel':
                    $result = $this->handleCancel($rekazService);
                    break;

                case 'delete':
                    $result = $this->handleDelete($rekazService);
                    break;

                default:
                    Log::warning("Unknown Rekaz sync action", [
                        'booking_id' => $this->booking->id,
                        'action' => $this->action,
                    ]);
                    return;
            }

            if ($result['success']) {
                Log::info("Rekaz sync completed successfully", [
                    'booking_id' => $this->booking->id,
                    'action' => $this->action,
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Rekaz sync job failed", [
                'booking_id' => $this->booking->id,
                'action' => $this->action,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // إعادة المحاولة
            throw $e;
        }
    }

    /**
     * معالجة إنشاء حجز جديد
     */
    protected function handleCreate(RekazService $rekazService): array
    {
        // تحقق من وجود mapping مسبق
        $mapping = $this->booking->rekazMapping;
        if ($mapping && $mapping->rekaz_id) {
            Log::info("Booking already synced with Rekaz", [
                'booking_id' => $this->booking->id,
                'rekaz_id' => $mapping->rekaz_id,
            ]);

            return [
                'success' => true,
                'data' => ['id' => $mapping->rekaz_id],
            ];
        }

        // تحويل البيانات
        $payload = $rekazService->transformBookingToRekazPayload($this->booking);

        // إنشاء في ركاز
        $result = $rekazService->createRekazBooking($payload);

        if (!$result['success']) {
            throw new \Exception("Failed to create booking in Rekaz: " . ($result['error'] ?? 'Unknown error'));
        }

        $rekazData = $result['data'];
        $reservationId = $rekazData['id'] ?? $rekazData['reservationId'] ?? null;

        if (!$reservationId) {
            throw new \Exception("Rekaz reservation ID not returned");
        }

        // ✅ حفظ mapping للـ Booking
        $this->booking->syncWithRekaz(
            $reservationId,
            'Reservation',
            [
                'invoice_id' => $rekazData['invoiceId'] ?? null,
                'payment_link' => $rekazData['paymentLink'] ?? null,
                'branch_id' => $payload['branchId'],
                'price_id' => $payload['items'][0]['priceId'] ?? null,
                'created_at' => now()->toDateTimeString(),
            ]
        );

        // ✅ إذا كان عميل جديد، احفظ customer_id
        if (isset($payload['customerDetails']) && isset($rekazData['customerId'])) {
            $this->booking->user->syncWithRekaz(
                $rekazData['customerId'],
                'Customer',
                [
                    'mobile' => $payload['customerDetails']['mobileNumber'],
                    'email' => $payload['customerDetails']['email'],
                    'created_via_booking' => true,
                    'booking_id' => $this->booking->id,
                    'synced_at' => now()->toDateTimeString(),
                ]
            );

            Log::info("Rekaz customer ID saved from booking response", [
                'user_id' => $this->booking->user_id,
                'rekaz_customer_id' => $rekazData['customerId'],
                'booking_id' => $this->booking->id,
            ]);
        }

        Log::info("Booking synced to Rekaz successfully", [
            'booking_id' => $this->booking->id,
            'rekaz_reservation_id' => $reservationId,
            'rekaz_invoice_id' => $rekazData['invoiceId'] ?? null,
            'used_customer_id' => isset($payload['customerId']),
            'used_customer_details' => isset($payload['customerDetails']),
        ]);

        return $result;
    }

    /**
     * معالجة تحديث حجز
     */
    protected function handleUpdate(RekazService $rekazService): array
    {
        $mapping = $this->booking->rekazMapping;
        if (!$mapping || !$mapping->rekaz_id) {
            Log::warning("Cannot update: booking not synced with Rekaz", [
                'booking_id' => $this->booking->id,
            ]);

            // إنشاء بدلاً من التحديث
            return $this->handleCreate($rekazService);
        }

        // TODO: تنفيذ update logic إذا لزم
        Log::info("Rekaz update not implemented yet", [
            'booking_id' => $this->booking->id,
            'rekaz_id' => $mapping->rekaz_id,
        ]);

        return ['success' => true];
    }

    protected function handleCancel(RekazService $rekazService): array
    {
        $mapping = $this->booking->rekazMapping;
        if (!$mapping || !$mapping->rekaz_id) {
            Log::warning("Cannot cancel: booking not synced with Rekaz", [
                'booking_id' => $this->booking->id,
            ]);
            return ['success' => false, 'error' => 'No Rekaz booking ID found'];
        }

        $reason = $this->booking->cancel_reason ?? null;
        $result = $rekazService->cancelReservation($mapping->rekaz_id, $reason);

        if ($result['success']) {
            Log::info("Booking cancelled in Rekaz successfully", [
                'booking_id' => $this->booking->id,
                'rekaz_id' => $mapping->rekaz_id,
            ]);
        }

        return $result;
    }

    protected function handleDelete(RekazService $rekazService): array
    {
        $mapping = $this->booking->rekazMapping;
        if (!$mapping || !$mapping->rekaz_id) {
            Log::warning("Cannot delete: booking not synced with Rekaz", [
                'booking_id' => $this->booking->id,
            ]);
            return ['success' => true]; // معتبر نجح لأنه ما كان موجود
        }

        $result = $rekazService->deleteReservation($mapping->rekaz_id);

        if ($result['success']) {
            // حذف الـ mapping
            $mapping->delete();

            Log::info("Booking deleted from Rekaz successfully", [
                'booking_id' => $this->booking->id,
                'rekaz_id' => $mapping->rekaz_id,
            ]);
        }

        return $result;
    }

    /**
     * تحديث بيانات ركاز في الحجز
     */
    // protected function updateBookingRekazData(array $result): void
    // {
    //     $meta = $this->booking->meta ?? [];

    //     // تخزين Rekaz booking ID إذا كان موجود
    //     if (isset($result['data']['id'])) {
    //         $meta['rekaz_booking_id'] = $result['data']['id'];
    //     }

    //     // تسجيل آخر مزامنة
    //     $meta['rekaz_last_sync'] = now()->toIso8601String();
    //     $meta['rekaz_last_action'] = $this->action;
    //     $meta['rekaz_synced'] = true;

    //     // حذف أخطاء سابقة إن وجدت
    //     unset($meta['rekaz_sync_error']);
    //     unset($meta['rekaz_sync_attempts']);

    //     $this->booking->update([
    //         'meta' => $meta,
    //     ]);
    // }

    /**
     * تسجيل خطأ المزامنة
     */
    // protected function recordSyncError(array $result): void
    // {
    //     $meta = $this->booking->meta ?? [];

    //     $meta['rekaz_sync_error'] = [
    //         'message' => $result['error'] ?? 'Unknown error',
    //         'status_code' => $result['status_code'] ?? null,
    //         'action' => $this->action,
    //         'timestamp' => now()->toIso8601String(),
    //         'attempt' => $this->attempts(),
    //     ];

    //     $meta['rekaz_synced'] = false;
    //     $meta['rekaz_sync_attempts'] = ($meta['rekaz_sync_attempts'] ?? 0) + 1;

    //     $this->booking->update([
    //         'meta' => $meta,
    //     ]);
    // }

    /**
     * معالجة فشل Job بعد كل المحاولات
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Rekaz sync job failed permanently", [
            'booking_id' => $this->booking->id,
            'action' => $this->action,
            'error' => $exception->getMessage(),
        ]);

        $meta = $this->booking->meta ?? [];
        $meta['rekaz_sync_failed'] = true;
        $meta['rekaz_sync_error'] = [
            'message' => $exception->getMessage(),
            'action' => $this->action,
            'timestamp' => now()->toIso8601String(),
            'attempts' => $this->tries,
        ];

        $this->booking->update([
            'meta' => $meta,
        ]);

        // يمكن إرسال إشعار للمسؤولين هنا
        // Notification::send($admins, new RekazSyncFailedNotification($this->booking));
    }

    /**
     * تحديد اسم الـ queue
     */
    // public function queue(): string
    // {
    //     return 'rekaz-sync';
    // }
}