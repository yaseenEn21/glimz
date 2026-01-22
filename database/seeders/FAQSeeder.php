<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('faqs')->insert([
            [
                'question'   => 'كيف أحجز موعد غسيل؟',
                'answer'     => 'يمكنك حجز موعد غسيل من الصفحة الرئيسية.',
                'sort_order' => 1,
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question'   => 'هل لازم أكون موجود وقت الغسيل؟',
                'answer'     => 'مو شرط تكون موجود، المهم توضح مكان السيارة وتكون المفاتيح متوفرة إذا احتاجها الفريق.',
                'sort_order' => 2,
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question'   => 'كيف أعدل أو ألغي الحجز؟',
                'answer'     => 'يمكنك الغاء الحجز من صفحة تفاصيل الحجز.',
                'sort_order' => 3,
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question'   => 'كيف أستخدم كود الخصم؟',
                'answer'     => 'عند سداد الفاتورة. ويمكنك نسخه من صفحة الأكواد المتاحة',
                'sort_order' => 4,
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question'   => 'كيف أتواصل مع الدعم؟',
                'answer'     => 'يمكنك الاتصال بالدعم عن طريق الواتساب.',
                'sort_order' => 5,
                'is_active'  => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);
    }
}