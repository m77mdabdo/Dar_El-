<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            ShippingMethodSeeder::class,
            SettingSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            CouponSeeder::class,
            BlogPostSeeder::class,
        ]);

        $customer = User::factory()->create([
            'name' => 'Test Customer',
            'email' => 'test@example.com',
        ]);
        $customer->assignRole('customer');
    }
}
