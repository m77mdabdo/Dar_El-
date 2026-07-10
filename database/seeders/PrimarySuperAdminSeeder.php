<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;

/**
 * Guarantees the configured primary Super Admin (config/primary_super_admin.php)
 * always exists, always holds the super_admin role, and always has every
 * permission — re-runnable on every deploy/seed so the account heals itself
 * even if it was ever accidentally changed. Touches only this one account;
 * every other user/role is left completely alone.
 */
class PrimarySuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('primary_super_admin.email');

        if (! $email) {
            return;
        }

        $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();

        if (! $user) {
            $password = env('PRIMARY_SUPER_ADMIN_PASSWORD') ?: Str::random(24);

            $user = User::create([
                'name' => 'Super Admin',
                'email' => $email,
                'password' => Hash::make($password),
            ]);

            $this->command?->warn("Primary Super Admin created ({$email}).");

            if (! env('PRIMARY_SUPER_ADMIN_PASSWORD')) {
                $this->command?->warn("Generated password (save this now, it will not be shown again): {$password}");
            }
        }

        $user->forceFill([
            'email_verified_at' => $user->email_verified_at ?? now(),
            'disabled_at' => null,
        ])->save();

        $user->syncRoles(['super_admin']);
        $user->syncPermissions(Permission::where('guard_name', 'web')->pluck('name')->all());
    }
}
