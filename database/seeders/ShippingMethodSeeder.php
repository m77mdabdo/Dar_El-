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
        ShippingMethod::updateOrCreate(
            ['code' => 'standard'],
            [
                'name_en' => 'Standard Delivery', 'name_ar' => 'توصيل عادي',
                'description_en' => 'Reliable delivery at an everyday price.',
                'description_ar' => 'توصيل موثوق بسعر مناسب.',
                'fee' => 75, 'estimated_days' => '3-5',
                'delivery_time_min_days' => 3, 'delivery_time_max_days' => 5,
                'is_active' => true, 'sort_order' => 0,
            ]
        );

        ShippingMethod::updateOrCreate(
            ['code' => 'express'],
            [
                'name_en' => 'Express Delivery', 'name_ar' => 'توصيل سريع',
                'description_en' => 'Faster delivery for when you need it sooner.',
                'description_ar' => 'توصيل أسرع عندما تحتاجين طلبك بسرعة.',
                'fee' => 150, 'estimated_days' => '1-2',
                'delivery_time_min_days' => 1, 'delivery_time_max_days' => 2,
                'is_active' => true, 'sort_order' => 1,
            ]
        );
    }
}
