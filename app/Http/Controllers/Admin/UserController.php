<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Mail\AdminUserWelcomeMail;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Spatie\Permission\Models\Permission;

/**
 * Manages staff accounts (super_admin/admin/employee) — deliberately
 * separate from Admin\CustomerController, which manages the `customer`
 * role. Both operate on the same underlying User model, but Laravel only
 * allows one auto-discovered Policy per model, so this controller uses
 * plain role/self checks instead of a Policy (mirroring the inline
 * abort_if/abort_unless convention CustomerController already used before
 * this feature). Route-level access is gated entirely by
 * SuperAdminMiddleware — only Super Admin ever reaches this controller.
 */
class UserController extends Controller
{
    protected const STAFF_ROLES = ['super_admin', 'admin', 'employee'];

    public function index(Request $request)
    {
        $users = User::whereHas('roles', fn ($q) => $q->whereIn('name', self::STAFF_ROLES))
            ->with('roles')
            ->when($request->role, fn ($q) => $q->whereHas('roles', fn ($r) => $r->where('name', $request->role)))
            ->when($request->search, fn ($q) => $q->where(fn ($sub) => $sub
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
            ))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $permissionGroups = config('permission_groups');
        $presets = config('permission_presets');

        return view('admin.users.create', compact('permissionGroups', 'presets'));
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
        ]);

        // email_verified_at isn't in User::$fillable (guarded like every
        // other timestamp-ish column), so it has to be set via forceFill()
        // rather than passed to create() — passing it to create() silently
        // no-ops instead of erroring, which is an easy miss.
        if ($request->boolean('email_verified')) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if ($user->isPrimarySuperAdmin()) {
            // The configured primary Super Admin (config/primary_super_admin.php)
            // is always forced to super_admin with every permission, even if
            // created here (via the UI) instead of by PrimarySuperAdminSeeder
            // — e.g. on a fresh install where the seeder hasn't run yet.
            $user->syncRoles(['super_admin']);
            $user->syncPermissions(Permission::where('guard_name', 'web')->pluck('name')->all());
        } else {
            $user->syncRoles([$validated['role']]);

            if ($validated['role'] === 'employee') {
                $user->syncPermissions($validated['permissions'] ?? []);
            }

            if (! $request->boolean('is_active', true)) {
                $user->forceFill(['disabled_at' => now()])->save();
            }
        }

        ActivityLog::record('created', $user, "Created {$validated['role']} account for {$user->name}");

        if ($request->boolean('send_welcome_email')) {
            Mail::to($user->email)->queue(new AdminUserWelcomeMail($user, $validated['password']));
        }

        return redirect()->route('admin.users.index')->with('status', __('users.created'));
    }

    public function edit(User $user)
    {
        abort_unless($user->hasAnyRole(self::STAFF_ROLES), 404);

        $permissionGroups = config('permission_groups');
        $presets = config('permission_presets');
        $userPermissions = $user->getDirectPermissions()->pluck('name')->all();

        return view('admin.users.edit', compact('user', 'permissionGroups', 'presets', 'userPermissions'));
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        abort_unless($user->hasAnyRole(self::STAFF_ROLES), 404);

        $validated = $request->validated();
        $oldRole = $user->getRoleNames()->first();

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);

        // withValidator() already rejected a role change on one's own
        // account, so this is safe to apply unconditionally here.
        if ($validated['role'] !== $oldRole) {
            $user->syncRoles([$validated['role']]);
            ActivityLog::record('role_changed', $user, "Changed role for {$user->name} from {$oldRole} to {$validated['role']}");
        }

        if ($user->isPrimarySuperAdmin()) {
            // Belt-and-suspenders: UpdateUserRequest already rejected a role
            // change, so this is always the 'else' branch in practice — but
            // force every permission onto this account regardless of what
            // was submitted, so it can never end up with less than full
            // access even if that guard is ever bypassed.
            $user->syncPermissions(Permission::where('guard_name', 'web')->pluck('name')->all());
        } elseif ($validated['role'] === 'employee') {
            $user->syncPermissions($validated['permissions'] ?? []);
            ActivityLog::record('permissions_changed', $user, "Updated permissions for {$user->name}");
        } else {
            // Super Admin/Admin rely entirely on hasAdminAccess()'s role
            // check, not direct grants — keep model_has_permissions clean
            // if an account is ever demoted back to employee later.
            $user->syncPermissions([]);
        }

        if ($user->id !== auth()->id() && ! $user->isPrimarySuperAdmin()) {
            $wasActive = ! $user->isDisabled();

            if ($request->boolean('is_active', true) !== $wasActive) {
                $user->forceFill(['disabled_at' => $request->boolean('is_active', true) ? null : now()])->save();
            }
        }

        ActivityLog::record('updated', $user, "Updated user {$user->name}");

        return redirect()->route('admin.users.index')->with('status', __('users.updated'));
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->hasAnyRole(self::STAFF_ROLES), 404);
        abort_if($user->id === auth()->id(), 403);
        abort_if($user->isPrimarySuperAdmin(), 403);

        if ($user->hasRole('super_admin') && User::role('super_admin')->count() <= 1) {
            return back()->with('error', __('users.cannot_delete_last_super_admin'));
        }

        $name = $user->name;
        $user->delete();

        ActivityLog::record('deleted', $user, "Deleted user {$name}");

        return redirect()->route('admin.users.index')->with('status', __('users.deleted'));
    }

    public function toggleActive(User $user): RedirectResponse
    {
        abort_unless($user->hasAnyRole(self::STAFF_ROLES), 404);
        abort_if($user->id === auth()->id(), 403);
        abort_if($user->isPrimarySuperAdmin(), 403);

        $user->forceFill(['disabled_at' => $user->isDisabled() ? null : now()])->save();

        ActivityLog::record($user->isDisabled() ? 'disabled' : 'enabled', $user, "Toggled active status for {$user->name}");

        return back()->with('status', $user->isDisabled() ? __('users.user_disabled') : __('users.user_enabled'));
    }

    public function resetPassword(User $user): RedirectResponse
    {
        abort_unless($user->hasAnyRole(self::STAFF_ROLES), 404);

        Password::sendResetLink(['email' => $user->email]);

        ActivityLog::record('password_reset', $user, "Sent password reset link to {$user->name}");

        return back()->with('status', __('users.reset_link_sent'));
    }

    public function forceLogout(User $user): RedirectResponse
    {
        abort_unless($user->hasAnyRole(self::STAFF_ROLES), 404);
        abort_if($user->id === auth()->id(), 403);

        DB::table('sessions')->where('user_id', $user->id)->delete();

        ActivityLog::record('force_logout', $user, "Forced logout for {$user->name}");

        return back()->with('status', __('users.force_logout_done'));
    }
}
