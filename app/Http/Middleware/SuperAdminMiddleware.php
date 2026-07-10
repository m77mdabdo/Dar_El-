<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restricts the User/Role/Permission management surfaces
 * (/admin/users*, /admin/roles*, /admin/permissions*) to Super Admin only —
 * Admin and Employee must never reach these, per the explicit security
 * requirement that only Super Admin can create/delete Admin or Super Admin
 * accounts or assign roles/permissions. Sibling of AdminMiddleware (same
 * guest→login / non-match→403 convention), nested *inside* that outer
 * gate in routes/admin.php rather than replacing it.
 *
 * @param  Closure(Request): (Response)  $next
 */
class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        abort_unless($request->user()->hasRole('super_admin'), 403);

        return $next($request);
    }
}
