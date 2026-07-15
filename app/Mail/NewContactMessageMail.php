<?php

namespace App\Mail;

use App\Models\ContactMessage;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewContactMessageMail extends Mailable
{
    use SerializesModels;

    public function __construct(public ContactMessage $contactMessage, public User $admin)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->admin->email, $this->admin->name)],
            subject: __('emails.admin_new_contact_message_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.new-contact-message',
            with: ['contactMessage' => $this->contactMessage],
        );
    }
}
