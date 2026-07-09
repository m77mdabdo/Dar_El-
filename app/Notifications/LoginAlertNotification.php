<?php

namespace App\Notifications;

use App\Mail\LoginAlertMail;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class LoginAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $ip,
        public string $device,
        public string $browser,
        public Carbon $time,
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
        return new LoginAlertMail($notifiable, $this->ip, $this->device, $this->browser, $this->time);
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
        ];
    }
}
