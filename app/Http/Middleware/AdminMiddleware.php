<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Restrict access to the admin panel to staff (super_admin/admin/
     * employee). Self-contained (doesn't assume 'auth' already ran
     * first): guests are sent to login, customers get a 403, staff pass
     * through. What an employee can actually SEE/DO once inside is then
     * narrowed by per-route/sidebar permission checks (User::hasAdminAccess()) —
     * this middleware only answers "may this user enter /admin at all".
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        abort_unless($request->user()->hasAnyRole(['admin', 'super_admin', 'employee']), 403);

        return $next($request);
    }
}
