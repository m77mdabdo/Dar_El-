<?php

namespace App\Jobs;

use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\InvoiceGenerationFailedAdminNotification;
use App\Services\InvoicePdfRenderer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

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
     *
     * The order confirmation email must always go out even if PDF
     * generation itself fails (a missing font, a disk/permissions issue on
     * a fresh deploy, dompdf hitting a memory limit, etc.) — previously a
     * failure here meant the customer received NO email at all, since
     * sending it was the last line of this same method. PDF generation is
     * now isolated so a failure there degrades to "confirmation without an
     * invoice attached" instead of "nothing sent".
     */
    public function handle(): void
    {
        $this->order->loadMissing(['items.product', 'shippingMethod', 'user']);

        $locale = $this->order->locale ?? config('app.locale');
        $invoice = $this->generateInvoice($locale);

        Mail::to($this->order->customer_email)->locale($locale)->send(new InvoiceMail($this->order, $invoice));
    }

    protected function generateInvoice(string $locale): ?Invoice
    {
        try {
            // Reuse the existing invoice's number/path if this order already
            // has one (e.g. a manual regeneration) so the invoice number
            // stays stable and the PDF file is replaced in place rather than
            // leaving an orphaned old file with a different name on disk.
            $existing = Invoice::where('order_id', $this->order->id)->first();
            $invoiceNumber = $existing?->invoice_number ?? $this->generateInvoiceNumber();
            $path = $existing?->pdf_path ?: 'invoices/'.Str::slug($invoiceNumber).'.pdf';

            // A queue worker has no HTTP-request locale context of its own,
            // so the invoice must explicitly render in the language the
            // customer actually checked out in (captured on the order at
            // that time), not whatever the worker process's default locale
            // happens to be.
            $previousLocale = App::getLocale();
            App::setLocale($locale);

            $pdf = app(InvoicePdfRenderer::class)->render('invoices.pdf', [
                'order' => $this->order,
                'invoiceNumber' => $invoiceNumber,
                'locale' => $locale,
                'isRtl' => $locale === 'ar',
                'djSupportEmail' => Setting::get('support_email'),
                'djWhatsapp' => Setting::get('whatsapp_number'),
            ]);
            Storage::disk('local')->put($path, $pdf);

            App::setLocale($previousLocale);

            return Invoice::updateOrCreate(
                ['order_id' => $this->order->id],
                [
                    'invoice_number' => $invoiceNumber,
                    'pdf_path' => $path,
                    'issued_at' => now(),
                ]
            );
        } catch (Throwable $e) {
            Log::error('Invoice generation failed', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
                'error' => $e->getMessage(),
            ]);

            Notification::send(User::admins(), new InvoiceGenerationFailedAdminNotification($this->order, $e->getMessage()));

            return null;
        }
    }

    protected function generateInvoiceNumber(): string
    {
        do {
            $invoiceNumber = 'INV-'.now()->format('Y').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Invoice::where('invoice_number', $invoiceNumber)->exists());

        return $invoiceNumber;
    }
}
