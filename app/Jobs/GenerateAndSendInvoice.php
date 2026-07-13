<?php

namespace App\Jobs;

use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\InvoicePdfService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
     * The order confirmation itself is sent immediately after checkout
     * (CheckoutController::store()), independent of this job — so a PDF
     * failure here never costs the customer their only email. This job's
     * only remaining email responsibility is the "invoice ready" follow-up,
     * sent once (and only once) generation actually succeeds; on failure,
     * generateInvoice() already logs and notifies admins, and there is
     * nothing further to send.
     */
    public function handle(InvoicePdfService $invoicePdfService): void
    {
        $locale = $this->order->locale ?? config('app.locale');
        $invoice = $invoicePdfService->generate($this->order, $locale);

        if (! $invoice) {
            return;
        }

        $customerEmail = $this->order->resolveCustomerEmail();

        if (! $customerEmail) {
            Log::warning('Invoice-ready email skipped: no resolvable customer email', [
                'order_id' => $this->order->id,
                'order_number' => $this->order->order_number,
            ]);

            return;
        }

        try {
            Mail::to($customerEmail)->locale($locale)->send(new InvoiceMail($this->order, $invoice));

            Log::info('Invoice-ready email dispatched', [
                'order_id' => $this->order->id,
                'mailable' => InvoiceMail::class,
                'recipient_masked' => Order::maskEmailForLogging($customerEmail),
                'status' => 'success',
            ]);
        } catch (Throwable $e) {
            Log::error('Invoice-ready email dispatch failed', [
                'order_id' => $this->order->id,
                'mailable' => InvoiceMail::class,
                'error' => $e->getMessage(),
                'status' => 'failed',
            ]);
        }
    }
}
