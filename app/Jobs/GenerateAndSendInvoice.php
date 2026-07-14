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
     * Kept comfortably under the Hostinger cron worker's own
     * --max-time=50 budget (see the queue:work cron command) so a single
     * slow attempt can never eat the whole cron window and starve every
     * other queued job behind it.
     */
    public int $timeout = 45;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    /**
     * Create a new job instance.
     */
    public function __construct(protected Order $order)
    {
        // Set at runtime rather than redeclared as a class property —
        // Queueable's own `public $queue;` has no default value, and PHP's
        // trait composition rejects a redeclaration that adds one.
        // PDF generation is the slowest work in the app; keeping it off
        // the 'high'/'default' queues means it can never delay an OTP
        // resend, a login alert, or an order-confirmation notification
        // that happens to be queued behind it.
        $this->queue = 'invoices';
    }

    /**
     * Execute the job.
     *
     * The order confirmation itself is sent immediately after checkout
     * (CheckoutController::store()), independent of this job — so a PDF
     * failure here never costs the customer their only email. This job's
     * only remaining email responsibility is the "invoice ready" follow-up.
     *
     * Both PDF generation and email sending are allowed to throw here
     * (nothing is silently swallowed into a false "success") — the order
     * itself was already safely committed in a separate, earlier
     * transaction before this job ever runs, so there is no risk in
     * letting a real failure become a real queue retry/failed-job entry
     * instead of vanishing into a log line nobody's watching.
     */
    public function handle(InvoicePdfService $invoicePdfService): void
    {
        $jobStartedAt = microtime(true);

        Log::info('Invoice job started', [
            'order_id' => $this->order->id,
            'job' => static::class,
            'queue' => $this->queue,
            'attempt' => $this->attempts(),
        ]);

        $locale = $this->order->locale ?? config('app.locale');

        // Skip regenerating a PDF that's already valid on disk — a retry
        // after e.g. a transient SMTP failure shouldn't re-render the
        // same file again, it should just resume from "send the email".
        $invoice = $this->order->invoice;

        if (! $invoice || ! $invoice->isDownloadable()) {
            $invoice = $invoicePdfService->generate($this->order, $locale);

            if (! $invoice) {
                // generate() already logged the specific cause and
                // notified admins; throwing here is what makes Laravel's
                // own retry/backoff and queue:failed bookkeeping apply,
                // instead of the customer being silently left with no
                // invoice and no record of anything having gone wrong.
                throw new \RuntimeException("Invoice PDF generation failed for order {$this->order->id}");
            }
        }

        if ($invoice->status === Invoice::STATUS_EMAILED) {
            // Already delivered on a previous attempt — never send the
            // same customer the same invoice twice.
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

        $mailStartedAt = microtime(true);

        Mail::to($customerEmail)->locale($locale)->send(new InvoiceMail($this->order, $invoice));

        $invoice->update(['status' => Invoice::STATUS_EMAILED, 'emailed_at' => now()]);

        Log::info('Invoice email sent', [
            'order_id' => $this->order->id,
            'mailable' => InvoiceMail::class,
            'recipient_masked' => Order::maskEmailForLogging($customerEmail),
            'status' => 'success',
            'smtp_duration_ms' => (int) ((microtime(true) - $mailStartedAt) * 1000),
            'job_duration_ms' => (int) ((microtime(true) - $jobStartedAt) * 1000),
        ]);
    }

    /**
     * Called once by Laravel after all $tries attempts are exhausted —
     * this is the terminal "give up" state, recorded on the invoice
     * itself so the customer-facing page can show a clear failure
     * message instead of "still preparing" forever, and so an admin can
     * see it needs manual attention without having to read the log.
     */
    public function failed(Throwable $e): void
    {
        Log::error('Invoice job failed', [
            'order_id' => $this->order->id,
            'job' => static::class,
            'queue' => $this->queue,
            'attempt' => $this->attempts(),
            'exception' => $e::class,
            'message' => $e->getMessage(),
        ]);

        Invoice::where('order_id', $this->order->id)->update([
            'status' => Invoice::STATUS_FAILED,
            'failed_reason' => \Illuminate\Support\Str::limit($e->getMessage(), 500),
        ]);
    }
}
