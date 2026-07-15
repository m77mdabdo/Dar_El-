<?php

namespace App\Mail;

use App\Models\Cart;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AbandonedCartReminderMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Cart $cart)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->cart->user->email, $this->cart->user->name)],
            subject: __('emails.cart_reminder_subject'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.carts.abandoned-reminder',
            with: ['cart' => $this->cart],
        );
    }
}
