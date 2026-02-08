<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AddressTestSeeder extends Seeder
{
    public function run(): void
    {
        // ✅ التأكد من وجود المستخدمين
        if (!User::find(4)) {
            $this->command->warn('⚠️  User #4 not found, skipping...');
        } else {
            $this->seedUserAddresses(4);
        }

        if (!User::find(5)) {
            $this->command->warn('⚠️  User #5 not found, skipping...');
        } else {
            $this->seedUserAddresses(5);
        }
    }

    private function seedUserAddresses(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            
            // ✅ حذف كامل (حتى الـ soft deleted)
            Address::withTrashed()
                ->where('user_id', $userId)
                ->forceDelete();

            // ✅ العنوان الأول (افتراضي + حالي) - البيت
            Address::create([
                'user_id' => $userId,
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
                'is_current_location' => true,
                'created_by' => $userId,
            ]);

            // ✅ عنوان العمل
            Address::create([
                'user_id' => $userId,
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
                'is_current_location' => false,
                'created_by' => $userId,
            ]);

            // ✅ عنوان آخر
            Address::create([
                'user_id' => $userId,
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
                'created_by' => $userId,
            ]);
        });

        $this->command->info("✅ Created 3 addresses for User #{$userId}");
    }
}