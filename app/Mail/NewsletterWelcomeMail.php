<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Template only — not wired into NewsletterController yet. Today
 * NewsletterSubscribed only notifies admins; the subscriber themselves
 * never receives an email. Ready for whenever that's wired in.
 */
class NewsletterWelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public NewsletterSubscriber $subscriber)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->subscriber->email)],
            subject: __('emails.newsletter_welcome_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.newsletter.welcome',
        );
    }
}
