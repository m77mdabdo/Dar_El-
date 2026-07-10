<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;

/**
 * Read-only view of the 4 system roles and what each one grants —
 * there's no arbitrary custom-role builder in this app (Employee access is
 * customized per-account via individual permission checkboxes instead, see
 * Admin\UserController). Gated entirely by SuperAdminMiddleware at the
 * route level.
 */
class RoleController extends Controller
{
    protected const SYSTEM_ROLES = ['super_admin', 'admin', 'employee', 'customer'];

    public function index()
    {
        $roles = Role::whereIn('name', self::SYSTEM_ROLES)
            ->withCount('users')
            ->get()
            ->sortBy(fn ($role) => array_search($role->name, self::SYSTEM_ROLES));

        return view('admin.roles.index', compact('roles'));
    }

    public function show(Role $role)
    {
        abort_unless(in_array($role->name, self::SYSTEM_ROLES, true), 404);

        $role->load('permissions');
        $permissionGroups = config('permission_groups');
        $rolePermissionNames = $role->permissions->pluck('name')->all();

        return view('admin.roles.show', compact('role', 'permissionGroups', 'rolePermissionNames'));
    }
}
