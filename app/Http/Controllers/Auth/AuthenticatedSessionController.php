<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function __construct(protected OtpService $otp) {}

    /**
     * Display the login view.
     */
    public function create(Request $request): View
    {
        // A JS-triggered auth prompt (e.g. "please login to save wishlist")
        // can't rely on Laravel's normal guest-redirect intended-URL capture
        // since it isn't a GET request against a guarded route. It passes
        // the page to return to explicitly instead.
        $redirect = $request->string('redirect');
        if ($redirect->isNotEmpty() && Str::startsWith($redirect, '/') && ! Str::startsWith($redirect, '//')) {
            $request->session()->put('url.intended', url($redirect->value()));
        }

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        if (! $user->hasVerifiedEmail()) {
            if ($this->otp->canResend($user)) {
                $this->otp->generate($user);
            }

            return redirect()->route('otp.notice');
        }

        return $user->redirectResponseAfterAuth();
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
