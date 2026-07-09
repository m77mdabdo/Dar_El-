<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class OtpMail extends Mailable
{
    /**
     * Sent synchronously (not queued) since the customer is actively
     * waiting on the code to continue registration/checkout.
     */
    public function __construct(public User $user, public string $otp, public int $expiresInMinutes)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->user->email, $this->user->name)],
            subject: __('emails.otp_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.otp',
            with: [
                'user' => $this->user,
                'otp' => $this->otp,
                'expiresInMinutes' => $this->expiresInMinutes,
            ],
        );
    }
}
