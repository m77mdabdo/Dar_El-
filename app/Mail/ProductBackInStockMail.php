<?php

namespace App\Mail;

use App\Models\Product;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Template only — not wired to any trigger yet. No back-in-stock
 * subscription feature exists in this app today; ready for whenever that
 * feature (customers opting in to be notified) is built.
 */
class ProductBackInStockMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public Product $product)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->user->email, $this->user->name)],
            subject: __('emails.back_in_stock_subject', ['product' => trans_field($this->product, 'name')]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.products.back-in-stock',
            with: ['user' => $this->user, 'product' => $this->product],
        );
    }
}
