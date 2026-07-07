<?php

namespace App\Services;

use App\Models\EmailVerificationOtp;
use App\Models\User;
use App\Notifications\OtpVerificationNotification;
use Illuminate\Support\Facades\Hash;

class OtpService
{
    protected const EXPIRES_IN_MINUTES = 10;

    protected const MAX_ATTEMPTS = 5;

    protected const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Generate a fresh OTP for the user, invalidate any previous ones, and send it.
     */
    public function generate(User $user): EmailVerificationOtp
    {
        $user->emailVerificationOtps()->whereNull('verified_at')->delete();

        $otp = (string) random_int(100000, 999999);

        $record = $user->emailVerificationOtps()->create([
            'email' => $user->email,
            'otp' => Hash::make($otp),
            'expires_at' => now()->addMinutes(self::EXPIRES_IN_MINUTES),
            'attempts' => 0,
        ]);

        $user->notify(new OtpVerificationNotification($otp, self::EXPIRES_IN_MINUTES));

        return $record;
    }

    /**
     * Verify the given code against the user's latest pending OTP.
     */
    public function verify(User $user, string $code): bool
    {
        $record = $user->emailVerificationOtps()
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $record || $record->isExpired() || $record->attempts >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (! Hash::check($code, $record->otp)) {
            $record->increment('attempts');

            return false;
        }

        $record->forceFill(['verified_at' => now()])->save();

        if (! $user->hasVerifiedEmail()) {
            $user->forceFill(['email_verified_at' => now()])->save();
            event(new \Illuminate\Auth\Events\Verified($user));
        }

        return true;
    }

    public function remainingAttempts(User $user): int
    {
        $record = $user->emailVerificationOtps()->whereNull('verified_at')->latest('id')->first();

        return $record ? max(0, self::MAX_ATTEMPTS - $record->attempts) : self::MAX_ATTEMPTS;
    }

    /**
     * Seconds remaining before the user is allowed to request another OTP.
     */
    public function resendCooldownRemaining(User $user): int
    {
        $record = $user->emailVerificationOtps()->latest('id')->first();

        if (! $record) {
            return 0;
        }

        $elapsed = now()->diffInSeconds($record->created_at);

        return max(0, self::RESEND_COOLDOWN_SECONDS - $elapsed);
    }

    public function canResend(User $user): bool
    {
        return $this->resendCooldownRemaining($user) <= 0;
    }
}
