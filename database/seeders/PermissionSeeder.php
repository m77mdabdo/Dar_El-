<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * The default permission set granted to a brand-new Employee account
     * (Super Admin can freely add/remove from this per account afterward —
     * this is just a sensible, low-privilege starting point).
     *
     * @var array<int, string>
     */
    protected const EMPLOYEE_DEFAULTS = [
        'dashboard.view',
        'products.view',
        'categories.view',
        'orders.view',
        'customers.view',
    ];

    public function run(): void
    {
        $all = collect(config('permission_groups'))->flatten();

        $all->each(fn (string $name) => Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $admin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $employee = Role::firstOrCreate(['name' => 'employee', 'guard_name' => 'web']);

        // Belt-and-suspenders: admin/super_admin also pass every check via
        // User::hasAdminAccess()'s role-based bypass regardless of what's
        // seeded here (so a bare test-created role with zero permissions
        // still gets full operational access) — this sync is what makes
        // the future Roles/Permissions UI show them as checked (truthful),
        // not what actually gates them.
        //
        // users.*/roles.*/permissions.* are the one exception: they're
        // Super-Admin-exclusive by explicit requirement, so admin is
        // deliberately NOT granted them here — hasAdminAccess() also
        // excludes admin's blanket bypass for these three prefixes, so
        // this omission is what actually enforces that exclusivity, not
        // just a cosmetic checkbox state.
        $superAdminOnly = fn (string $name) => str_starts_with($name, 'users.')
            || str_starts_with($name, 'roles.')
            || str_starts_with($name, 'permissions.');

        $superAdmin->syncPermissions($all->all());
        $admin->syncPermissions($all->reject($superAdminOnly)->all());

        $employee->syncPermissions(self::EMPLOYEE_DEFAULTS);
    }
}
