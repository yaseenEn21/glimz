<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationTemplate;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * ==========================
         * Customer Booking Status Notifications
         * ==========================
         * Placeholders you can use:
         * {booking_id}, {branch_name}, {date}, {time},
         * {service_name}, {package_name},
         * {employee_name}, {vehicle_plate}
         */

        // pending
        NotificationTemplate::updateOrCreate(
            ['key' => 'booking_status_pending_customer'],
            [
                'title' => 'تم استلام طلب الحجز',
                'body' => 'تم استلام طلبك #{booking_id}. سنقوم بتأكيده قريبًا.',
                'title_en' => 'Booking request received',
                'body_en' => 'We received your booking #{booking_id}. We’ll confirm it shortly.',
                'description' => 'للزبون: عند إنشاء الحجز وحالته pending.',
                'is_active' => true,
            ]
        );

        // confirmed (this is the "special" confirmation notification)
        NotificationTemplate::updateOrCreate(
            ['key' => 'booking_status_confirmed_customer'],
            [
                'title' => 'تم تأكيد الحجز ✅',
                'body' => 'تم تأكيد حجزك #{booking_id} بتاريخ {date} {time}.',
                'title_en' => 'Booking confirmed ✅',
                'body_en' => 'Your booking #{booking_id} is confirmed for {date} {time}.',
                'description' => 'للزبون: عند تأكيد الحجز confirmed.',
                'is_active' => true,
            ]
        );

        // moving
        NotificationTemplate::updateOrCreate(
            ['key' => 'booking_status_moving_customer'],
            [
                'title' => 'في الطريق إليك',
                'body' => 'فريقنا في الطريق لحجزك #{booking_id}.',
                'title_en' => 'On the way',
                'body_en' => 'Our team is on the way for booking #{booking_id}.',
                'description' => 'للزبون: عند تغيير الحالة إلى moving.',
                'is_active' => true,
            ]
        );

        // arrived
        NotificationTemplate::updateOrCreate(
            ['key' => 'booking_status_arrived_customer'],
            [
                'title' => 'وصلنا ✅',
                'body' => 'تم الوصول لموقعك لحجز #{booking_id}.',
                'title_en' => 'We arrived ✅',
                'body_en' => 'We arrived at your location for booking #{booking_id}.',
                'description' => 'للزبون: عند تغيير الحالة إلى arrived.',
                'is_active' => true,
            ]
        );

        // completed
        NotificationTemplate::updateOrCreate(
            ['key' => 'booking_status_completed_customer'],
            [
                'title' => 'تم إكمال الخدمة ✨',
                'body' => 'تم إكمال حجزك #{booking_id}. شكرًا لاختيارك لنا.',
                'title_en' => 'Completed ✨',
                'body_en' => 'Your booking #{booking_id} is completed. Thanks for choosing us.',
                'description' => 'للزبون: عند تغيير الحالة إلى completed.',
                'is_active' => true,
            ]
        );

        // cancelled
        NotificationTemplate::updateOrCreate(
            ['key' => 'booking_status_cancelled_customer'],
            [
                'title' => 'تم إلغاء الحجز',
                'body' => 'تم إلغاء حجز #{booking_id}. إذا كان هناك خطأ تواصل معنا.',
                'title_en' => 'Booking cancelled',
                'body_en' => 'Booking #{booking_id} has been cancelled. If this is a mistake, contact us.',
                'description' => 'للزبون: عند تغيير الحالة إلى cancelled.',
                'is_active' => true,
            ]
        );

        /**
         * ==========================
         * Admin Dashboard Notification
         * ==========================
         */

        NotificationTemplate::updateOrCreate(
            ['key' => 'booking_created_admin'],
            [
                'title' => 'حجز جديد 🆕',
                'body' => 'تم إضافة حجز جديد #{booking_id} ({date} {time}).',
                'title_en' => 'New booking 🆕',
                'body_en' => 'A new booking #{booking_id} was created ({date} {time}).',
                'description' => 'للأدمن (لوحة التحكم): عند إنشاء حجز جديد.',
                'is_active' => true,
            ]
        );
    }
}