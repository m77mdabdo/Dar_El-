<?php

namespace App\Mail;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginAlertMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $ip,
        public string $device,
        public string $browser,
        public Carbon $time,
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->user->email, $this->user->name)],
            subject: __('emails.login_alert_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.login-alert',
            with: [
                'user' => $this->user,
                'ip' => $this->ip,
                'device' => $this->device,
                'browser' => $this->browser,
                'time' => $this->time,
            ],
        );
    }
}
