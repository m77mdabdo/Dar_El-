<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\HtmlString;

class OtpVerificationNotification extends Notification
{
    /**
     * Sent synchronously (not queued) since the customer is actively
     * waiting on the code to continue registration/checkout.
     */
    public function __construct(public string $otp, public int $expiresInMinutes)
    {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Your Dar El-Jamila verification code'))
            ->greeting(__('Hello :name', ['name' => $notifiable->name]))
            ->line(__('Use the code below to verify your account. This code expires in :minutes minutes.', ['minutes' => $this->expiresInMinutes]))
            ->line(new HtmlString('<div style="text-align:center;font-size:32px;font-weight:700;letter-spacing:8px;color:#601526;margin:16px 0;">'.$this->otp.'</div>'))
            ->line(__('If you did not request this code, you can safely ignore this email.'));
    }
}
