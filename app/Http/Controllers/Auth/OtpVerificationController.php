<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\OtpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

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
            return $user->redirectResponseAfterAuth();
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
            return $user->redirectResponseAfterAuth();
        }

        if (! $this->otp->verify($user, $request->input('otp'))) {
            return back()->withErrors([
                'otp' => $this->otp->remainingAttempts($user) > 0
                    ? __('The code you entered is incorrect. Please try again.')
                    : __('Too many incorrect attempts. Please request a new code.'),
            ]);
        }

        return $user->redirectResponseAfterAuth()
            ->with('status', __('Your account has been verified. Welcome to Dar El Jamila!'));
    }

    /**
     * Resend a fresh OTP code.
     */
    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $user->redirectResponseAfterAuth();
        }

        if (! $this->otp->canResend($user)) {
            return back()->withErrors([
                'otp' => __('Please wait :seconds seconds before requesting a new code.', ['seconds' => $this->otp->resendCooldownRemaining($user)]),
            ]);
        }

        // The success message below must only ever be shown once the mail
        // transport has actually accepted the message — generate() sends
        // synchronously and throws on a transport failure, so a caught
        // exception here means the customer genuinely was not sent a code.
        try {
            $this->otp->generate($user);
        } catch (Throwable $e) {
            Log::warning('OTP resend failed', ['user_id' => $user->id]);

            return back()->withErrors([
                'otp' => __('We could not send a new code right now. Please try again in a moment.'),
            ]);
        }

        return back()->with('status', __('A new code has been sent to your email.'));
    }
}
