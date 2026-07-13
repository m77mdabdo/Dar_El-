<?php

namespace App\Mail;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class InvoiceMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * $invoice is nullable — null when this is the immediate "order placed"
     * confirmation dispatched right after checkout (CheckoutController),
     * before any PDF exists; set when this is the later "invoice ready"
     * follow-up dispatched by GenerateAndSendInvoice once generation
     * succeeds. Same Mailable/view for both, since the view already adapts
     * its copy and attachment to whichever state it's given.
     */
    public function __construct(public Order $order, public ?Invoice $invoice = null)
    {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->invoice
                ? __('emails.invoice_ready_subject', ['number' => $this->order->order_number])
                : __('emails.order_confirmation_subject', ['number' => $this->order->order_number]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.orders.confirmation',
            with: ['order' => $this->order, 'invoice' => $this->invoice],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (! $this->invoice || ! $this->invoice->pdf_path || ! Storage::disk('local')->exists($this->invoice->pdf_path)) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('local', $this->invoice->pdf_path)
                ->as("{$this->invoice->invoice_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}
