<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OtpVerificationController extends Controller
{
    public function __construct(protected OtpService $otp) {}

    /**
     * Display the OTP verification prompt.
     */
    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended($user->postLoginRedirectRoute());
        }

        return view('auth.verify-otp', [
            'email' => $user->email,
            'resendCooldown' => $this->otp->resendCooldownRemaining($user),
        ]);
    }

    /**
     * Verify the submitted OTP code.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended($user->postLoginRedirectRoute());
        }

        if (! $this->otp->verify($user, $request->input('otp'))) {
            return back()->withErrors([
                'otp' => $this->otp->remainingAttempts($user) > 0
                    ? __('The code you entered is incorrect. Please try again.')
                    : __('Too many incorrect attempts. Please request a new code.'),
            ]);
        }

        return redirect()->intended($user->postLoginRedirectRoute())
            ->with('status', __('Your account has been verified. Welcome to Dar El-Jamila!'));
    }

    /**
     * Resend a fresh OTP code.
     */
    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended($user->postLoginRedirectRoute());
        }

        if (! $this->otp->canResend($user)) {
            return back()->withErrors([
                'otp' => __('Please wait :seconds seconds before requesting a new code.', ['seconds' => $this->otp->resendCooldownRemaining($user)]),
            ]);
        }

        $this->otp->generate($user);

        return back()->with('status', __('A new code has been sent to your email.'));
    }
}
