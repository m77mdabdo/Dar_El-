<?php

namespace App\Mail;

use App\Models\BackInStockSubscription;
use App\Models\Product;
use App\Models\ProductSize;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

/**
 * Sent by BackInStockService when a subscribed size (or, for a
 * whole-product subscription, the product's only/no size) crosses from 0
 * to positive stock. The subscriber may be a guest — email-only, no
 * account — so this is addressed by email, never by a User relation.
 */
class ProductBackInStockMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public BackInStockSubscription $subscription,
        public Product $product,
        public ?ProductSize $size = null,
    ) {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->subscription->email)],
            subject: __('emails.back_in_stock_subject', ['product' => trans_field($this->product, 'name')]),
        );
    }

    public function content(): Content
    {
        // The subscription may not be persisted yet (the admin email-preview
        // tool builds a sample, unsaved one) — a signed route needs a real
        // id, so fall back to a harmless placeholder link in that case.
        $unsubscribeUrl = $this->subscription->exists
            ? URL::signedRoute('back-in-stock.unsubscribe', ['subscription' => $this->subscription->id])
            : '#';

        return new Content(
            view: 'emails.products.back-in-stock',
            with: [
                'product' => $this->product,
                'size' => $this->size,
                'unsubscribeUrl' => $unsubscribeUrl,
            ],
        );
    }
}
