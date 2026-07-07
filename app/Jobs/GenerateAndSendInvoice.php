<?php

namespace App\Jobs;

use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GenerateAndSendInvoice implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(protected Order $order)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->order->loadMissing(['items', 'shippingMethod', 'user']);

        $invoiceNumber = 'INV-'.now()->format('Y').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);

        while (Invoice::where('invoice_number', $invoiceNumber)->exists()) {
            $invoiceNumber = 'INV-'.now()->format('Y').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        }

        $pdf = Pdf::loadView('invoices.pdf', ['order' => $this->order, 'invoiceNumber' => $invoiceNumber]);
        $path = 'invoices/'.Str::slug($invoiceNumber).'.pdf';
        Storage::disk('local')->put($path, $pdf->output());

        $invoice = Invoice::updateOrCreate(
            ['order_id' => $this->order->id],
            [
                'invoice_number' => $invoiceNumber,
                'pdf_path' => $path,
                'issued_at' => now(),
            ]
        );

        Mail::to($this->order->customer_email)->send(new InvoiceMail($this->order, $invoice));
    }
}
