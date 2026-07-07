<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireVerifiedIfAuthenticated
{
    /**
     * Checkout requires a logged-in, OTP-verified customer. Guests are sent
     * to login/register (their cart stays intact in the session); logged-in
     * but unverified customers are sent to the OTP page. Laravel remembers
     * the originally requested checkout URL so the customer lands back here
     * automatically once they finish authenticating.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'))
                ->with('status', __('Please login or create an account to complete your order.'));
        }

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return redirect()->route('otp.notice')
                ->with('status', __('Please verify your account to complete your order.'));
        }

        return $next($request);
    }
}
