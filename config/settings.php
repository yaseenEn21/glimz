<?php

return [

    // ─── قواعد الحجوزات ───────────────────────────────────────
    [
        'key' => 'bookings.cancel_allowed_minutes',
        'label' => 'الوقت المسموح للإلغاء (بالدقائق)',
        'type' => 'integer',
        'default' => 1440, // 24 ساعة = 1440 دقيقة
    ],
    [
        'key' => 'bookings.edit_allowed_minutes',
        'label' => 'الوقت المسموح للتعديل (بالدقائق)',
        'type' => 'integer',
        'default' => 180, // 3 ساعات = 180 دقيقة
    ],

    // ─── النقاط ───────────────────────────────────────────────
    [
        'key' => 'points.redeem_points',
        'label' => 'نقاط الاسترداد',
        'type' => 'integer',
        'default' => 100,
    ],
    [
        'key' => 'points.redeem_amount',
        'label' => 'قيمة الاسترداد (ريال)',
        'type' => 'integer',
        'default' => 10,
    ],
    [
        'key' => 'points.min_redeem_points',
        'label' => 'الحد الأدنى للاسترداد',
        'type' => 'integer',
        'default' => 100,
    ],

    // ─── الحجوزات ─────────────────────────────────────────────
    [
        'key' => 'bookings.cancel_reasons',
        'label' => 'أسباب الإلغاء',
        'type' => 'json',
        'default' => json_encode([
            ['code' => 'change_time', 'name' => ['ar' => 'تغيير الموعد', 'en' => 'Change Time']],
            ['code' => 'change_mind', 'name' => ['ar' => 'تغيير الرأي', 'en' => 'Change Mind']],
            ['code' => 'other', 'name' => ['ar' => 'سبب آخر', 'en' => 'Other']],
        ], JSON_UNESCAPED_UNICODE),
    ],

    // ─── التواصل ──────────────────────────────────────────────
    [
        'key' => 'contact_phone',
        'label' => 'رقم الهاتف',
        'type' => 'string',
        'default' => '0590000000',
    ],
    [
        'key' => 'contact_whatsapp',
        'label' => 'واتساب',
        'type' => 'string',
        'default' => 'wa.me/0590000000',
    ],
    [
        'key' => 'contact_email',
        'label' => 'الإيميل',
        'type' => 'string',
        'default' => 'info@gmail.com',
    ],

];

// التحقق من إمكانية الإلغاء
// $cancelAllowedMinutes = (int) Setting::get('bookings.cancel_allowed_minutes', 1440);
// $bookingDateTime = Carbon::parse($booking->booking_date . ' ' . $booking->start_time);

// if (now()->diffInMinutes($bookingDateTime, false) < $cancelAllowedMinutes) {
//     return response()->json(['message' => 'لا يمكن الإلغاء، تجاوزت الوقت المسموح'], 422);
// }

// // التحقق من إمكانية التعديل
// $editAllowedMinutes = (int) Setting::get('bookings.edit_allowed_minutes', 180);

// if (now()->diffInMinutes($bookingDateTime, false) < $editAllowedMinutes) {
//     return response()->json(['message' => 'لا يمكن التعديل، تجاوزت الوقت المسموح'], 422);
// }