<?php

namespace Database\Seeders;

use App\Models\ExternalPaymentMethod;
use Illuminate\Database\Seeder;

class ExternalPaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $methods = [
            [
                'name' => ['ar' => 'تحويل بنكي', 'en' => 'Bank Transfer'],
                'description' => [
                    'ar' => 'الدفع عن طريق التحويل البنكي',
                    'en' => 'Payment via bank transfer'
                ],
                'code' => 'bank_transfer',
                'icon' => 'ki-duotone ki-bank',
                'requires_reference' => true,
                'requires_attachment' => true,
                'bank_details' => [
                    'Bank Name' => 'Al Rajhi Bank',
                    'IBAN' => 'SA12 3456 7890 1234 5678 9012',
                    'Account Name' => 'Company Name',
                    'Account Number' => '1234567890',
                ],
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => ['ar' => 'نقدي', 'en' => 'Cash'],
                'description' => [
                    'ar' => 'الدفع نقداً',
                    'en' => 'Cash payment'
                ],
                'code' => 'cash',
                'icon' => 'ki-duotone ki-dollar',
                'requires_reference' => false,
                'requires_attachment' => false,
                'bank_details' => null,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => ['ar' => 'شيك', 'en' => 'Cheque'],
                'description' => [
                    'ar' => 'الدفع بواسطة شيك',
                    'en' => 'Payment by cheque'
                ],
                'code' => 'cheque',
                'icon' => 'ki-duotone ki-note-2',
                'requires_reference' => true,
                'requires_attachment' => true,
                'bank_details' => null,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => ['ar' => 'نقطة بيع (POS)', 'en' => 'Point of Sale (POS)'],
                'description' => [
                    'ar' => 'الدفع عبر جهاز نقطة البيع',
                    'en' => 'Payment via POS terminal'
                ],
                'code' => 'pos',
                'icon' => 'ki-duotone ki-card',
                'requires_reference' => true,
                'requires_attachment' => false,
                'bank_details' => null,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => ['ar' => 'مدى', 'en' => 'Mada'],
                'description' => [
                    'ar' => 'الدفع ببطاقة مدى',
                    'en' => 'Payment with Mada card'
                ],
                'code' => 'mada',
                'icon' => 'ki-duotone ki-credit-cart',
                'requires_reference' => true,
                'requires_attachment' => false,
                'bank_details' => null,
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($methods as $method) {
            ExternalPaymentMethod::updateOrCreate(
                ['code' => $method['code']],
                $method
            );
        }

        $this->command->info('External payment methods seeded successfully!');
    }
}