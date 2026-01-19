<?php

return [

    'title' => 'لوحة التحكم',

    'kpi' => [
        'title' => 'لوحة مؤشرات الأداء (KPI)',
        'subtitle' => 'افتراضيًا الشهر الحالي — وتقدر تغيّر المدة',

        'actions' => [
            'refresh' => 'تحديث',
        ],

        'cards' => [
            'total_bookings' => 'إجمالي الحجوزات',
            'active_bookings' => 'حجوزات فعّالة',
            'completed' => 'مكتملة',
            'cancelled' => 'ملغية',
            'cancel_rate' => 'نسبة الإلغاء %',
            'package_bookings' => 'حجوزات بالباقات',
            'gross' => 'إجمالي المبيعات',
            'paid' => 'المدفوع',
            'unpaid' => 'غير مدفوع',
            'avg_ticket' => 'متوسط الفاتورة',
        ],

        'sections' => [
            'status_distribution' => 'توزيع الحالات',
            'trend_daily' => 'الحجوزات والمدفوعات (يوميًا)',
            'top_bikers' => 'أفضل الموظفين',
            'top_services' => 'أفضل الخدمات',
        ],

        'charts' => [
            'series' => [
                'bookings' => 'الحجوزات',
                'paid' => 'المدفوع',
            ],
            'bars' => [
                'bookings_count' => 'عدد الحجوزات',
            ],
        ],
    ],

];