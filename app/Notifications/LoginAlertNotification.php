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
        public ?string $provider = null,
    ) {
        // Set at runtime rather than redeclared as a class property —
        // Queueable's own `public $queue;` has no default value, and PHP's
        // trait composition rejects a redeclaration that adds one.
        // High-priority queue: a security-relevant, auth-adjacent alert
        // must never sit behind slower default-queue work (order/cart
        // notifications) or invoice PDF generation.
        $this->queue = 'high';
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
