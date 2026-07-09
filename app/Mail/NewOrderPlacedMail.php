<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewOrderPlacedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * $admin is the specific admin this copy is addressed to — required
     * since toMail() returning a raw Mailable is NOT auto-addressed by
     * Laravel's notification MailChannel the way a MailMessage would be.
     */
    public function __construct(public Order $order, public User $admin)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->admin->email, $this->admin->name)],
            subject: __('emails.admin_new_order_subject', ['number' => $this->order->order_number]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.new-order',
            with: ['order' => $this->order],
        );
    }
}
