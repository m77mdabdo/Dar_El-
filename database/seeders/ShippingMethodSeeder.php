<?php

namespace Database\Seeders;

use App\Models\ShippingMethod;
use Illuminate\Database\Seeder;

class ShippingMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        ShippingMethod::firstOrCreate(
            ['name_en' => 'Standard Delivery'],
            ['name_ar' => 'توصيل عادي', 'fee' => 75, 'estimated_days' => '3-5', 'is_active' => true]
        );

        ShippingMethod::firstOrCreate(
            ['name_en' => 'Express Delivery'],
            ['name_ar' => 'توصيل سريع', 'fee' => 150, 'estimated_days' => '1-2', 'is_active' => true]
        );
    }
}
