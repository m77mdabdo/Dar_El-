<?php

namespace App\Listeners;

use App\Models\Setting;
use App\Notifications\LoginAlertNotification;
use App\Support\UserAgentParser;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Throwable;

class SendLoginAlertNotification
{
    /**
     * Deliberately NOT queued: must run synchronously within the original
     * login request so request()->ip()/userAgent() reflect that request,
     * not a queue worker's unrelated context. The actual mail/database send
     * (LoginAlertNotification) is queued on its own.
     */
    public function handle(Login $event): void
    {
        if (Setting::get('login_alerts_enabled', '1') !== '1') {
            return;
        }

        // Auth::attempt() fires this event before LoginRequest's disabled-
        // account check runs and logs the user back out — without this
        // guard a disabled account would get a "new login" email for a
        // login the app itself immediately reversed a moment later.
        if ($event->user->isDisabled()) {
            return;
        }

        $userAgent = Request::userAgent();

        // Set by SocialAuthController right before Auth::login() for an
        // OAuth login, and nowhere else — pulled (not just read) so it
        // never leaks into a later, unrelated login in the same session.
        $provider = session()->pull('login_via_provider');

        // Sent synchronously (LoginAlertNotification is no longer
        // ShouldQueue), so a transport failure here happens inside the
        // login/register request itself — must degrade to "alert not sent"
        // rather than fail the request, same philosophy as the try/catch
        // already around OTP sending (OtpService::send).
        try {
            $event->user->notify(new LoginAlertNotification(
                ip: Request::ip(),
                device: UserAgentParser::device($userAgent),
                browser: UserAgentParser::browser($userAgent),
                time: now(),
                provider: $provider,
            ));
        } catch (Throwable $e) {
            Log::warning('Login alert notification failed', [
                'user_id' => $event->user->id,
                'exception' => $e::class,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
