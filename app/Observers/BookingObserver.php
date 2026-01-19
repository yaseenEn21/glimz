<?php

namespace App\Observers;

use App\Jobs\AwardBookingPointsJob;
use App\Models\Booking;
use App\Models\User;
use App\Services\NotificationService;

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

        // 2) إشعار الزبون لو الحجز تم إنشاؤه بحالة pending (اختياري لكنه غالبًا مطلوب)
        if (($booking->status ?? null) === 'pending') {
            $this->notifyCustomerStatus($booking, 'pending');
        }
    }

    /**
     * عند تحديث الحجز
     */
    public function updated(Booking $booking): void
    {
        // فقط إذا status تغيّر
        if (!$booking->wasChanged('status')) {
            return;
        }

        $booking->loadMissing(['user', 'service', 'employee']);

        $newStatus = (string) $booking->status;

        // تحديث تواريخ حسب الحالة (اختياري)
        $this->syncStatusTimestamps($booking, $newStatus);

        // إشعار الزبون بالحالة الجديدة
        $this->notifyCustomerStatus($booking, $newStatus);

        // ✅ هنا الإضافة
        if ($newStatus === 'completed') {
            $actorId = $booking->updated_by ?? auth()->id();
            AwardBookingPointsJob::dispatch((int) $booking->id, $actorId)->afterCommit();
        }
    }

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

        $locale = $user->locale ?? 'ar'; // عدّل حسب حقلك (إن وجد)

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

    private function notifyAdminsNewBooking(Booking $booking): void
    {
        // ✅ اختَر أدمنز حسب نظامك:
        // إذا عندك Spatie:
        // $admins = User::role('admin')->where('is_active', 1)->where('notification', 1)->get();

        // خيار عام (لو عندك حقل is_admin):
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

    private function formatDate(Booking $booking): string
    {
        // booking_date casted date:Y-m-d عندك، فممتاز
        return $booking->booking_date?->format('Y-m-d') ?? '';
    }

    private function formatTime(Booking $booking): string
    {
        // start_time غالبًا string (HH:MM:SS أو HH:MM)
        $t = (string) ($booking->start_time ?? '');
        if ($t === '')
            return '';
        return substr($t, 0, 5);
    }

    private function safeServiceName(Booking $booking, string $locale): string
    {
        $service = $booking->service;
        if (!$service)
            return '';

        // لو name json {"ar":"..","en":".."}
        $name = data_get($service->name, $locale);
        return is_string($name) ? $name : (string) ($service->name ?? '');
    }

    private function safeEmployeeName(Booking $booking): string
    {
        $emp = $booking->employee;
        if (!$emp)
            return '';
        return (string) ($emp->name ?? '');
    }
}