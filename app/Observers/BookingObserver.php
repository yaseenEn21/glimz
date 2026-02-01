<?php

namespace App\Observers;

use App\Jobs\AwardBookingPointsJob;
use App\Jobs\SendPartnerWebhookJob;
use App\Jobs\SyncBookingToRekazJob;
use App\Models\Booking;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\RekazService;
use Illuminate\Support\Facades\Log;

class BookingObserver
{
    public function __construct(private NotificationService $notifications)
    {
    }

    /**
     * عند إنشاء حجز جديد
     */
    public function created(Booking $booking): void
    {
        // حمّل العلاقات المطلوبة مرة واحدة
        $booking->loadMissing(['user', 'service', 'employee']);

        // 1) إشعار الأدمن بوجود حجز جديد
        $this->notifyAdminsNewBooking($booking);

        // 2) إشعار الزبون لو الحجز تم إنشاؤه بحالة pending
        if (($booking->status ?? null) === 'pending') {
            $this->notifyCustomerStatus($booking, 'pending');
        }

        // ✅ 3) مزامنة مع ركاز (بعد الإنشاء الكامل)
        // ملاحظة: هذا يتم dispatch من الـ Controller مباشرة
        // لكن لو تريد أمان إضافي يمكن dispatch هنا أيضاً
        // $this->syncToRekaz($booking, 'create');
    }

    /**
     * عند تحديث الحجز
     */
    public function updated(Booking $booking): void
    {
        // فقط إذا status تغيّر
        if ($booking->wasChanged('status')) {
            $booking->loadMissing(['user', 'service', 'employee']);

            $newStatus = (string) $booking->status;

            // تحديث تواريخ حسب الحالة
            $this->syncStatusTimestamps($booking, $newStatus);

            // إشعار الزبون بالحالة الجديدة
            $this->notifyCustomerStatus($booking, $newStatus);

            // ✅ منح النقاط عند الإكمال
            if ($newStatus === 'completed') {
                $actorId = $booking->updated_by ?? auth()->id();
                AwardBookingPointsJob::dispatch((int) $booking->id, $actorId)->afterCommit();
            }

            // ✅ مزامنة مع ركاز حسب الحالة
            $action = match ($newStatus) {
                'confirmed' => 'confirm',
                'cancelled' => 'cancel',
                default => 'update',
            };

            $this->syncToRekaz($booking, $action);

            return; // ✅ مهم: نرجع هنا عشان ما ندخل للـ if التالي
        }

        // ✅ إذا ما تغير الـ status، بس تغيرت حقول تانية مهمة
        if ($this->hasRekazRelevantChanges($booking)) {
            Log::info('Rekaz relevant fields changed', [
                'booking_id' => $booking->id,
                'changed_fields' => array_keys($booking->getChanges()),
            ]);

            $this->syncToRekaz($booking, 'update');
        }

        // ✅ إرسال Webhook للشريك
        if ($booking->partner_id && $booking->wasChanged('status')) {
            $this->sendPartnerWebhook($booking);
        }
    }

    /**
     * عند حذف الحجز نهائياً
     */
    public function deleted(Booking $booking): void
    {
        // عند حذف الحجز نهائياً، نحذفه من ركاز
        $this->syncToRekaz($booking, 'delete');
    }

    /**
     * مزامنة مع ركاز
     */
    /**
     * مزامنة مع ركاز
     */
    private function syncToRekaz(Booking $booking, string $action): void
    {
        // استخدام الدالة الجديدة للتحقق من التفعيل
        $rekazService = app(RekazService::class);

        if (!$rekazService->isSyncEnabled($action)) {
            Log::debug('Rekaz sync disabled for this action', [
                'booking_id' => $booking->id,
                'action' => $action,
            ]);
            return;
        }

        try {
            // للإنشاء: لا نزامن إذا لم يكن له ID في ركاز بعد
            // (سيتم المزامنة من الـ Controller)
            if ($action === 'create') {
                $mapping = $booking->rekazMapping;
                if ($mapping && $mapping->rekaz_id) {
                    // تم إنشاؤه مسبقاً، لا حاجة للمزامنة مرة أخرى
                    Log::debug('Rekaz sync skipped - already created', [
                        'booking_id' => $booking->id,
                    ]);
                    return;
                }
            }

            // للتحديث/التأكيد/الإلغاء: نتحقق من وجود ID في ركاز
            if (in_array($action, ['update', 'confirm', 'cancel'])) {
                $mapping = $booking->rekazMapping;

                // إذا لم يكن له ID في ركاز، لا داعي للمزامنة
                if (!$mapping || !$mapping->rekaz_id) {
                    Log::debug('Rekaz sync skipped - no rekaz_id', [
                        'booking_id' => $booking->id,
                        'action' => $action,
                    ]);
                    return;
                }
            }

            // تحديد البيانات المحدثة فقط إذا كان تحديث
            $updateData = null;
            if ($action === 'update') {
                $updateData = $this->getRekazUpdatedFields($booking);
            }

            // dispatch الـ Job
            $delay = config('services.rekaz.sync.delay_seconds', 2);
            $queue = config('services.rekaz.sync.queue', 'rekaz-sync');

            SyncBookingToRekazJob::dispatch($booking, $action, $updateData)
                ->onQueue($queue)
                ->delay(now()->addSeconds($delay));

            Log::info('Rekaz sync job dispatched from observer', [
                'booking_id' => $booking->id,
                'action' => $action,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to dispatch Rekaz sync job from observer', [
                'booking_id' => $booking->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * التحقق من وجود تغييرات تحتاج مزامنة مع ركاز
     */
    private function hasRekazRelevantChanges(Booking $booking): bool
    {
        // الحقول المهمة التي تحتاج مزامنة
        $importantFields = [
            'booking_date',   // ✅ تاريخ الحجز
            'start_time',     // ✅ وقت البداية
            'end_time',       // ✅ وقت النهاية
            'employee_id',    // ✅ الموظف
        ];

        foreach ($importantFields as $field) {
            if ($booking->wasChanged($field)) {
                return true;
            }
        }

        return false;
    }

    /**
     * جلب الحقول المحدثة فقط لإرسالها لركاز
     */
    private function getRekazUpdatedFields(Booking $booking): array
    {
        $updateData = [];
        $changes = $booking->getChanges();

        // تعيين الحقول المحدثة
        if (isset($changes['status'])) {
            $updateData['status'] = app(RekazService::class)->mapStatusToRekaz($booking->status);
        }

        if (isset($changes['booking_date'])) {
            $updateData['date'] = $booking->booking_date;
        }

        if (isset($changes['start_time'])) {
            $updateData['start_time'] = $booking->start_time;
        }

        if (isset($changes['end_time'])) {
            $updateData['end_time'] = $booking->end_time;
        }

        if (isset($changes['employee_id'])) {
            $updateData['employee_id'] = $booking->employee_id ? (string) $booking->employee_id : null;
        }

        if (isset($changes['rating'])) {
            $updateData['rating'] = $booking->rating;
            $updateData['rating_comment'] = $booking->rating_comment;
        }

        // إذا لم يكن هناك تحديثات محددة، أرسل كامل البيانات
        if (empty($updateData)) {
            return app(\App\Services\RekazService::class)->transformBookingData($booking);
        }

        return $updateData;
    }

    /**
     * إشعار الزبون بحالة الحجز
     */
    private function notifyCustomerStatus(Booking $booking, string $status): void
    {
        $user = $booking->user;

        if (!$user || !$user->is_active || !$user->notification) {
            return;
        }

        $templateKey = $this->customerTemplateKeyForStatus($status);
        if (!$templateKey) {
            return;
        }

        $locale = $user->locale ?? 'ar';

        $templateData = [
            'booking_id' => $booking->id,
            'date' => $this->formatDate($booking),
            'time' => $this->formatTime($booking),
            'service_name' => $this->safeServiceName($booking, $locale),
            'employee_name' => $this->safeEmployeeName($booking),
        ];

        $extraData = [
            'model' => 'booking',
            'model_id' => $booking->id,
            'status' => $status,
            'user_id' => $booking->user_id,
            'service_id' => $booking->service_id,
        ];

        $this->notifications->sendToUserUsingTemplate(
            user: $user,
            templateKey: $templateKey,
            templateData: $templateData,
            extraData: $extraData,
            locale: $locale,
            createdBy: $booking->updated_by ?? auth()->id(),
        );
    }

    /**
     * إشعار الأدمنز بحجز جديد
     */
    private function notifyAdminsNewBooking(Booking $booking): void
    {
        // اختر أدمنز حسب نظامك
        $admins = User::query()
            ->where('is_active', 1)
            ->where('notification', 1)
            ->where('user_type', 'admin')
            ->get();

        if ($admins->isEmpty())
            return;

        $templateKey = 'booking_created_admin';
        $locale = 'ar';

        $templateData = [
            'booking_id' => $booking->id,
            'date' => $this->formatDate($booking),
            'time' => $this->formatTime($booking),
            'service_name' => $this->safeServiceName($booking, $locale),
            'employee_name' => $this->safeEmployeeName($booking),
        ];

        $extraData = [
            'model' => 'booking',
            'model_id' => $booking->id,
            'status' => $booking->status,
            'user_id' => $booking->user_id,
        ];

        foreach ($admins as $admin) {
            $this->notifications->sendToUserUsingTemplate(
                user: $admin,
                templateKey: $templateKey,
                templateData: $templateData,
                extraData: $extraData,
                locale: $admin->locale ?? $locale,
                createdBy: $booking->created_by ?? auth()->id(),
            );
        }
    }

    /**
     * الحصول على template key حسب الحالة
     */
    private function customerTemplateKeyForStatus(string $status): ?string
    {
        return match ($status) {
            'pending' => 'booking_status_pending_customer',
            'confirmed' => 'booking_status_confirmed_customer',
            'moving' => 'booking_status_moving_customer',
            'arrived' => 'booking_status_arrived_customer',
            'completed' => 'booking_status_completed_customer',
            'cancelled' => 'booking_status_cancelled_customer',
            default => null,
        };
    }

    /**
     * تحديث timestamps حسب الحالة
     */
    private function syncStatusTimestamps(Booking $booking, string $status): void
    {
        // مهم: لا تعمل save داخل observer بدون حماية لتفادي loop
        // هنا نعمل updateQuietly (Laravel 9+)
        if ($status === 'confirmed' && empty($booking->confirmed_at)) {
            $booking->updateQuietly(['confirmed_at' => now()]);
        }

        if ($status === 'cancelled' && empty($booking->cancelled_at)) {
            $booking->updateQuietly(['cancelled_at' => now()]);
        }
    }

    /**
     * تنسيق التاريخ
     */
    private function formatDate(Booking $booking): string
    {
        return $booking->booking_date?->format('Y-m-d') ?? '';
    }

    /**
     * تنسيق الوقت
     */
    private function formatTime(Booking $booking): string
    {
        $t = (string) ($booking->start_time ?? '');
        if ($t === '')
            return '';
        return substr($t, 0, 5);
    }

    /**
     * الحصول على اسم الخدمة بأمان
     */
    private function safeServiceName(Booking $booking, string $locale): string
    {
        $service = $booking->service;
        if (!$service)
            return '';

        // لو name json {"ar":"..","en":".."}
        $name = data_get($service->name, $locale);
        return is_string($name) ? $name : (string) ($service->name ?? '');
    }

    /**
     * الحصول على اسم الموظف بأمان
     */
    private function safeEmployeeName(Booking $booking): string
    {
        $emp = $booking->employee;
        if (!$emp)
            return '';
        return (string) ($emp->name ?? '');
    }

    /**
     * إرسال webhook للشريك
     */
    private function sendPartnerWebhook(Booking $booking): void
    {
        if (!in_array($booking->status, ['moving', 'arrived', 'completed', 'cancelled'])) {
            return;
        }

        SendPartnerWebhookJob::dispatch($booking)
            ->onQueue('webhooks')
            ->delay(now()->addSeconds(2));

        Log::info('Partner webhook job dispatched', [
            'booking_id' => $booking->id,
            'partner_id' => $booking->partner_id,
            'status' => $booking->status,
        ]);
    }

}