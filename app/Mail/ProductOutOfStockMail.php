<?php

namespace App\Mail;

use App\Models\Product;
use App\Models\ProductSize;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProductOutOfStockMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Product $product, public ProductSize $size, public User $admin)
    {
        //
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            to: [new Address($this->admin->email, $this->admin->name)],
            subject: __('emails.admin_out_of_stock_subject', ['product' => $this->product->name_en]),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.out-of-stock',
            with: ['product' => $this->product, 'size' => $this->size],
        );
    }
}
