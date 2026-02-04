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
                'question' => json_encode([
                    'ar' => 'كيف أحجز موعد غسيل؟',
                    'en' => 'How do I book a car wash appointment?',
                ]),
                'answer' => json_encode([
                    'ar' => 'يمكنك حجز موعد غسيل من الصفحة الرئيسية أو من خلال قسم الخدمات في التطبيق.',
                    'en' => 'You can book a car wash appointment from the home page or through the services section in the app.',
                ]),
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question' => json_encode([
                    'ar' => 'هل لازم أكون موجود وقت الغسيل؟',
                    'en' => 'Do I need to be present during the car wash?',
                ]),
                'answer' => json_encode([
                    'ar' => 'مو شرط تكون موجود، المهم توضح مكان السيارة وتكون المفاتيح متوفرة إذا احتاجها الفريق.',
                    'en' => 'You don\'t need to be present, just make sure to specify the car location and have the keys available if the team needs them.',
                ]),
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question' => json_encode([
                    'ar' => 'كيف أعدل أو ألغي الحجز؟',
                    'en' => 'How do I modify or cancel my booking?',
                ]),
                'answer' => json_encode([
                    'ar' => 'يمكنك تعديل أو إلغاء الحجز من صفحة تفاصيل الحجز في التطبيق.',
                    'en' => 'You can modify or cancel your booking from the booking details page in the app.',
                ]),
                'sort_order' => 3,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question' => json_encode([
                    'ar' => 'كيف أستخدم كود الخصم؟',
                    'en' => 'How do I use a discount code?',
                ]),
                'answer' => json_encode([
                    'ar' => 'يمكنك إدخال كود الخصم عند سداد الفاتورة. يمكنك نسخ الكود من صفحة الأكواد المتاحة.',
                    'en' => 'You can enter the discount code when paying the invoice. You can copy the code from the available codes page.',
                ]),
                'sort_order' => 4,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question' => json_encode([
                    'ar' => 'كيف أتواصل مع الدعم؟',
                    'en' => 'How do I contact support?',
                ]),
                'answer' => json_encode([
                    'ar' => 'يمكنك التواصل مع فريق الدعم عن طريق الواتساب أو الاتصال المباشر من خلال التطبيق.',
                    'en' => 'You can contact the support team via WhatsApp or direct call through the app.',
                ]),
                'sort_order' => 5,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question' => json_encode([
                    'ar' => 'ما هي وسائل الدفع المتاحة؟',
                    'en' => 'What payment methods are available?',
                ]),
                'answer' => json_encode([
                    'ar' => 'نوفر الدفع عن طريق البطاقات الائتمانية، Apple Pay، STC Pay والدفع عند الاستلام.',
                    'en' => 'We accept credit cards, Apple Pay, STC Pay, and cash on delivery.',
                ]),
                'sort_order' => 6,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'question' => json_encode([
                    'ar' => 'كم تستغرق مدة الخدمة؟',
                    'en' => 'How long does the service take?',
                ]),
                'answer' => json_encode([
                    'ar' => 'تختلف مدة الخدمة حسب نوع الخدمة المختارة، وتتراوح عادة بين 30 دقيقة إلى ساعتين.',
                    'en' => 'Service duration varies depending on the selected service type, typically ranging from 30 minutes to 2 hours.',
                ]),
                'sort_order' => 7,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }
}