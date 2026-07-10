<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates admin routes that have no natural Eloquent subject for a Policy
 * (Settings, Newsletter, Contact Messages, admin Notifications, admin Cart
 * view) — for model-backed resources (Product/Category/Order/etc.), a
 * Policy + $this->authorize() is used instead. Registered as the
 * 'admin.permission' middleware alias; usage: ->middleware('admin.permission:settings.view').
 *
 * Deliberately its own middleware rather than Spatie's stock `permission:`
 * alias — it delegates to User::hasAdminAccess(), which always passes for
 * admin/super_admin regardless of whether permissions happen to be seeded
 * in a given environment (see hasAdminAccess() docblock).
 */
class EnsureAdminPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        abort_unless($request->user()?->hasAdminAccess($permission), 403);

        return $next($request);
    }
}
