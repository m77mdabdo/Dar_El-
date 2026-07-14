<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            'store_name' => 'Dar El Jamila',
            'support_email' => 'support@darel-jamila.com',
            'whatsapp_number' => '+201000000000',
            'default_shipping_fee' => '75',
            'facebook_url' => '',
            'instagram_url' => '',
            'login_alerts_enabled' => '1',
        ];

        foreach ($defaults as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
