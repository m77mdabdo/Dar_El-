<?php

namespace App\Notifications;

use App\Mail\LoginAlertMail;
use Carbon\Carbon;
use Illuminate\Notifications\Notification;

class LoginAlertNotification extends Notification
{
    /**
     * Sent synchronously (not queued) — a security-relevant, auth-adjacent
     * alert must reach the inbox immediately, not wait for the next
     * cron-driven queue:work tick (up to 60s on this host).
     */
    public function __construct(
        public string $ip,
        public string $device,
        public string $browser,
        public Carbon $time,
        public ?string $provider = null,
    ) {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): LoginAlertMail
    {
        return new LoginAlertMail($notifiable, $this->ip, $this->device, $this->browser, $this->time, $this->provider);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'login_alert',
            'ip' => $this->ip,
            'device' => $this->device,
            'browser' => $this->browser,
            'time' => $this->time->toIso8601String(),
            'provider' => $this->provider,
        ];
    }
}
