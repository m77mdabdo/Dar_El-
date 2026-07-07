<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Coupon::firstOrCreate(
            ['code' => 'WELCOME10'],
            [
                'type' => 'percentage',
                'value' => 10,
                'min_order_amount' => 500,
                'max_uses' => 100,
                'is_active' => true,
                'expires_at' => now()->addMonths(6),
            ]
        );
    }
}
