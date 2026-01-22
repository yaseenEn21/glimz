<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ContactSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        DB::table('settings')->upsert([
            [
                'key'        => 'contact_phone',
                'value'      => '0590000000',   
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key'        => 'contact_whatsapp',
                'value'      => 'wa.me/0590000000', 
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key'        => 'contact_email',
                'value'      => 'info@gmail.com', 
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['key'], ['value', 'updated_at']);
    }
}