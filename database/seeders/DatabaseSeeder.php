<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

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
            PermissionSeeder::class,
            PrimarySuperAdminSeeder::class,
            ShippingMethodSeeder::class,
            SettingSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            CouponSeeder::class,
            BlogPostSeeder::class,
        ]);

        $this->backfillLegacyIsAdminColumn();

        $customer = User::factory()->create([
            'name' => 'Test Customer',
            'email' => 'test@example.com',
        ]);
        $customer->assignRole('customer');

        if (app()->environment('local', 'testing')) {
            $superAdmin = User::factory()->create([
                'name' => 'Dev Super Admin',
                'email' => 'superadmin@example.test',
            ]);
            $superAdmin->assignRole('super_admin');
        }
    }

    /**
     * Defensive, re-runnable backfill for the legacy `is_admin` boolean
     * column (superseded by Spatie roles, but never actually wired up or
     * dropped — see `2026_07_07_092212_add_is_admin_to_users_table.php`).
     * Runs as a seeder step (not a migration) so the `admin` role from
     * RoleSeeder is guaranteed to already exist by the time this runs.
     */
    protected function backfillLegacyIsAdminColumn(): void
    {
        if (! Schema::hasColumn('users', 'is_admin')) {
            return;
        }

        User::where('is_admin', true)->get()->each(function (User $user) {
            if (! $user->hasAnyRole(['admin', 'super_admin'])) {
                $user->assignRole('admin');
            }
        });
    }
}
