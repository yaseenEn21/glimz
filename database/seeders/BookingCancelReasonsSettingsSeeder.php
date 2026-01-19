<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BookingCancelReasonsSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $reasons = [
            [
                'code' => 'change_time',
                'name' => ['ar' => 'بدي أغيّر موعد الحجز', 'en' => 'I want to change the booking time'],
            ],
            [
                'code' => 'change_address',
                'name' => ['ar' => 'بدي أغيّر العنوان', 'en' => 'I want to change the address'],
            ],
            [
                'code' => 'no_longer_needed',
                'name' => ['ar' => 'لم أعد بحاجة للخدمة', 'en' => 'I no longer need the service'],
            ],
            [
                'code' => 'price_high',
                'name' => ['ar' => 'السعر مرتفع', 'en' => 'The price is high'],
            ],
            [
                'code' => 'found_alternative',
                'name' => ['ar' => 'وجدت بديل آخر', 'en' => 'I found an alternative'],
            ],
            [
                'code' => 'not_available',
                'name' => ['ar' => 'لم أعد متاحًا في هذا الوقت', 'en' => 'I am not available at this time'],
            ],
            [
                'code' => 'other',
                'name' => ['ar' => 'سبب آخر', 'en' => 'Other'],
            ],
        ];

        DB::table('settings')->updateOrInsert(
            ['key' => 'bookings.cancel_reasons'],
            [
                'value' => json_encode($reasons, JSON_UNESCAPED_UNICODE),
                'updated_at' => $now,
                'created_at' => $now,
            ]
        );
    }
}