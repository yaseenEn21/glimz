<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PointsSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $rows = [
            ['key' => 'points.redeem_points', 'value' => '100', 'type' => 'int', 'label' => 'عدد النقاط للاسترداد'],
            ['key' => 'points.redeem_amount', 'value' => '10', 'type' => 'int', 'label' => 'قيمة الاسترداد (ريال)'],
            ['key' => 'points.min_redeem_points', 'value' => '100', 'type' => 'int', 'label' => 'الحد الادنى للاسترداد'],
            ['key' => 'points.auto_award_booking_points', 'value' => '1', 'type' => 'boolean', 'label' => 'منح النقاط تلقائياً عند اكتمال الحجز',],
        ];

        foreach ($rows as $row) {
            DB::table('settings')->updateOrInsert(
                ['key' => $row['key']],
                [
                    'value' => $row['value'],
                    'type' => $row['type'] ?? null,
                    'label' => $row['label'] ?? null,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }

        DB::table('settings')->insertOrIgnore([
            'key' => 'first_booking_discount',
            'value' => json_encode([
                'is_active' => false,
                'discount_type' => 'percentage',   // percentage | fixed | special_price
                'discount_value' => 0,
                'applies_to_service_ids' => [],              // [] = جميع الخدمات
            ]),
            'type' => 'json',
            'label' => 'خصم أول حجز',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
