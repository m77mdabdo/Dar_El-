<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

/**
 * Read-only, searchable/grouped catalog of every permission slug in the
 * system — mirrors the same grouping used by the Employee permission
 * checkboxes (config/permission_groups.php) so Super Admin can see the
 * full picture in one place. Gated entirely by SuperAdminMiddleware.
 */
class PermissionController extends Controller
{
    public function index()
    {
        $permissionGroups = config('permission_groups');

        return view('admin.permissions.index', compact('permissionGroups'));
    }
}
