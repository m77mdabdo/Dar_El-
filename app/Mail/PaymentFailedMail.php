<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Template only — not wired to any trigger yet. This app is currently
 * COD-only with no payment gateway integrated, so nothing dispatches this
 * Mailable today. Ready for whenever online payment is added.
 */
class PaymentFailedMail extends Mailable
{
    use SerializesModels;

    public function __construct(public Order $order, public ?string $reason = null)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->order->customer_email, $this->order->customer_name)],
            subject: __('emails.payment_failed_subject', ['number' => $this->order->order_number]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.payment-failed',
            with: ['order' => $this->order, 'reason' => $this->reason],
        );
    }
}
