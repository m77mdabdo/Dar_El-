<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Restrict access to the admin panel to users with the 'admin' role.
     * Self-contained (doesn't assume 'auth' already ran first): guests are
     * sent to login, logged-in non-admins get a 403, admins pass through.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()) {
            return redirect()->route('login');
        }

        abort_unless($request->user()->hasRole('admin'), 403);

        return $next($request);
    }
}
