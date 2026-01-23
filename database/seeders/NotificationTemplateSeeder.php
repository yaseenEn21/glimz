<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationTemplate;

class NotificationTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $iconsBasePath = public_path('assets/media/icons/duotune/notifications');

        /**
         * ==========================
         * Customer Booking Status Notifications
         * ==========================
         */

        // pending - استخدام أيقونة الوقت/الساعة
        $pending = NotificationTemplate::updateOrCreate(
            ['key' => 'booking_status_pending_customer'],
            [
                'title' => 'تم استلام طلب الحجز',
                'body' => 'تم استلام طلبك #{booking_id}. سنقوم بتأكيده قريبًا.',
                'title_en' => 'Booking request received',
                'body_en' => 'We received your booking #{booking_id}. we will confirm it shortly.',
                'description' => 'للزبون: عند إنشاء الحجز وحالته pending.',
                'is_active' => true,
            ]
        );
        $this->attachIcon($pending, $iconsBasePath . '/time.png'); // ⏰ ساعة - في انتظار التأكيد

        // confirmed - استخدام أيقونة الصح/التأكيد
        $confirmed = NotificationTemplate::updateOrCreate(
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
        $this->attachIcon($confirmed, $iconsBasePath . '/check.png'); // ✅ علامة صح - تم التأكيد

        // moving - استخدام أيقونة السيارة/الموقع
        $moving = NotificationTemplate::updateOrCreate(
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
        $this->attachIcon($moving, $iconsBasePath . '/location-car.png'); // 🚗 سيارة - في الطريق

        // arrived - استخدام أيقونة الموقع
        $arrived = NotificationTemplate::updateOrCreate(
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
        $this->attachIcon($arrived, $iconsBasePath . '/location-car.png'); // 📍 موقع - وصلنا

        // completed - استخدام أيقونة النجمة/الهدية
        $completed = NotificationTemplate::updateOrCreate(
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
        $this->attachIcon($completed, $iconsBasePath . '/gift-box.png'); // 🎁 هدية - اكتمل بنجاح

        // cancelled - استخدام أيقونة X/الإلغاء
        $cancelled = NotificationTemplate::updateOrCreate(
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
        $this->attachIcon($cancelled, $iconsBasePath . '/cancel.png'); // ❌ إلغاء

        /**
         * ==========================
         * Admin Dashboard Notification
         * ==========================
         */

        // admin - استخدام أيقونة الحجز/التقويم
        $admin = NotificationTemplate::updateOrCreate(
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
        $this->attachIcon($admin, $iconsBasePath . '/booking.png'); // 📅 حجز جديد

    }

    /**
     * ربط أيقونة بالـ Template باستخدام Spatie Media Library
     */
    protected function attachIcon(NotificationTemplate $template, string $iconPath): void
    {
        if (!file_exists($iconPath)) {
            $this->command->warn("Icon not found: {$iconPath}");
            return;
        }

        $template->clearMediaCollection('icon');

        $template->addMedia($iconPath)
            ->preservingOriginal()
            ->toMediaCollection('icon');

        $this->command->info("Icon attached to: {$template->key}");
    }
}