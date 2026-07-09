<?php

namespace App\Notifications;

use App\Mail\OtpMail;
use Illuminate\Notifications\Notification;

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

    public function toMail(object $notifiable): OtpMail
    {
        return new OtpMail($notifiable, $this->otp, $this->expiresInMinutes);
    }
}
