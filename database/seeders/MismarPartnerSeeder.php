<?php

namespace Database\Seeders;

use App\Models\Partner;
use Illuminate\Database\Seeder;

class MismarPartnerSeeder extends Seeder
{
    public function run(): void
    {
        $partner = Partner::updateOrCreate(
            ['username' => 'mismar'],
            [
                'name' => 'مسمار',
                'email' => 'mismar@gmail.com',
                'mobile' => null,
                'webhook_url' => 'https://api.mismarapp.com',
                'webhook_type' => 'mismar',
                'daily_booking_limit' => 20,
                'is_active' => true,
            ]
        );

        $this->command->info("✅ Mismar partner created/updated");
        $this->command->info("   Token: {$partner->api_token}");
    }
}