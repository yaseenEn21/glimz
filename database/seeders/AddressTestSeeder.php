<?php

namespace Database\Seeders;

use App\Models\Address;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddressTestSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {

            // ✅ تنظيف العناوين السابقة للمستخدم
            Address::where('user_id', 4)->delete();

            // ✅ العنوان الأول → is_default = true & is_current_location = true
            Address::create([
                'user_id' => 4,
                'type' => 'home',
                'country' => 'Saudi Arabia',
                'city' => 'Jeddah',
                'area' => 'Al Hamdaniyah',
                'address_line' => 'Al Hamdaniyah District - Jeddah',
                'building_name' => 'لا يوجد',
                'building_number' => '15',
                'landmark' => 'تموينات العزيزية',
                'lat' => 21.4207,
                'lng' => 39.0888,
                'is_default' => true,
                'is_current_location' => true, // ✅ الموقع الحالي
            ]);

            // ✅ العنوان الثاني → is_default = false & is_current_location = false
            Address::create([
                'user_id' => 4,
                'type' => 'work',
                'country' => 'Saudi Arabia',
                'city' => 'Jeddah',
                'area' => 'Al Rawdah',
                'address_line' => 'Work Location - Jeddah',
                'building_name' => 'برج رقم واحد',
                'building_number' => '5',
                'landmark' => 'برج الوحدة',
                'lat' => 21.5561111,
                'lng' => 39.2258333,
                'is_default' => false,
                'is_current_location' => false, // ✅ ليس الموقع الحالي
            ]);

            // ✅ عنوان ثالث (اختياري)
            Address::create([
                'user_id' => 4,
                'type' => 'other',
                'country' => 'Saudi Arabia',
                'city' => 'Jeddah',
                'area' => 'Al Salamah',
                'address_line' => 'Friend House - Jeddah',
                'building_name' => null,
                'building_number' => '22',
                'landmark' => 'بالقرب من مسجد الفاروق',
                'lat' => 21.6358,
                'lng' => 39.1058,
                'is_default' => false,
                'is_current_location' => false,
            ]);
        });
    }
}