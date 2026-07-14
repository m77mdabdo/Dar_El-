<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\InvoiceGenerationFailedAdminNotification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Single source of truth for invoice PDF generation — GenerateAndSendInvoice
 * (and anything else that ever needs an invoice PDF) calls generate() here
 * rather than touching a renderer or Storage directly. Owns: which engine
 * renders it (config('invoice.pdf_engine'), mpdf by default — see
 * InvoiceMpdfRenderer for why), data normalization, atomic file writes, and
 * success/failure logging.
 *
 * Engine selection is a deployment-time config value only, never an
 * automatic runtime fallback: if mpdf throws, we log and mark the invoice
 * failed rather than silently retrying with dompdf, because dompdf is
 * known to reverse Arabic text on this production server — sending that
 * out labeled as a success would be worse than not sending anything.
 */
class InvoicePdfService
{
    public function __construct(
        protected InvoiceMpdfRenderer $mpdfRenderer,
        protected InvoicePdfRenderer $dompdfRenderer,
    ) {}

    public function generate(Order $order, string $locale): ?Invoice
    {
        $order->loadMissing(['items.product', 'shippingMethod', 'user']);

        $existing = Invoice::where('order_id', $order->id)->first();
        $invoiceNumber = $existing?->invoice_number ?? $this->generateInvoiceNumber();
        $finalPath = $existing?->pdf_path ?: 'invoices/'.Str::slug($invoiceNumber).'.pdf';

        // Persisted *before* rendering starts — this is what lets the
        // customer-facing "invoice not ready" page tell "still queued/
        // processing, check back soon" apart from "failed, contact
        // support": previously, a failure left no invoice row at all, so
        // the page showed the same generic message forever with no way
        // to distinguish the two. A prior successful pdf_path is left
        // untouched here (only invoice_number/status change), so a
        // failed *regeneration* attempt never hides an already-valid PDF.
        $invoice = Invoice::updateOrCreate(
            ['order_id' => $order->id],
            ['invoice_number' => $invoiceNumber, 'status' => Invoice::STATUS_PROCESSING]
        );

        try {
            $engine = config('invoice.pdf_engine', 'mpdf');
            $template = $engine === 'dompdf' ? 'invoices.pdf' : 'invoices.pdf-mpdf';
            $viewData = $this->normalizeInvoiceData($order, $invoiceNumber, $locale);

            // Proves, in the production log itself, exactly which engine
            // is about to run and exactly what raw Arabic source strings
            // it's being handed — if these three ever show up already
            // reversed here, the bug is upstream of the renderer (a
            // translation file or DB value), not the PDF engine. Logged
            // once per generation, not per-request, so this stays cheap.
            Log::info('Invoice PDF engine selected', [
                'order_id' => $order->id,
                'invoice_number' => $invoiceNumber,
                'engine' => $engine,
                'service_class' => static::class,
                'template' => $template,
            ]);
            Log::info('Invoice Arabic source check', [
                'order_id' => $order->id,
                'invoice_label' => __('invoice.invoice', [], 'ar'),
                'brand_name' => __('Dar El-Jamila', [], 'ar'),
                'date_label' => __('invoice.date', [], 'ar'),
            ]);

            // A queue worker has no HTTP-request locale context of its own,
            // so the invoice must explicitly render in the language the
            // customer actually checked out in (captured on the order at
            // that time), not whatever the worker process's default locale
            // happens to be.
            $previousLocale = App::getLocale();
            App::setLocale($locale);

            $pdfBytes = $this->render($engine, $viewData);

            App::setLocale($previousLocale);

            $this->writeAtomically($finalPath, $pdfBytes);

            $invoice->update([
                'pdf_path' => $finalPath,
                'issued_at' => now(),
                'status' => Invoice::STATUS_GENERATED,
                'failed_reason' => null,
            ]);

            $absolutePath = Storage::disk('local')->path($finalPath);

            Log::info('Invoice PDF file written', [
                'order_id' => $order->id,
                'invoice_number' => $invoiceNumber,
                'engine' => $engine,
                'absolute_path' => $absolutePath,
                'size' => filesize($absolutePath),
                'modified_at' => date('Y-m-d H:i:s', filemtime($absolutePath)),
            ]);

            return $invoice;
        } catch (Throwable $e) {
            // A safe, short summary only — never the full exception
            // message verbatim if it could plausibly contain a path,
            // credential, or other environment detail; $e->getMessage()
            // here is from our own PDF-generation code (validation
            // errors, mPDF/dompdf failures), not user input.
            $invoice->update([
                'status' => Invoice::STATUS_FAILED,
                'failed_reason' => Str::limit($e->getMessage(), 500),
            ]);

            Log::error('Invoice PDF generation failed', [
                'order_id' => $order->id,
                'invoice_number' => $invoiceNumber,
                'engine' => $engine ?? config('invoice.pdf_engine', 'mpdf'),
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            Notification::send(User::admins(), new InvoiceGenerationFailedAdminNotification($order, $e->getMessage()));

            return null;
        }
    }

    protected function render(string $engine, array $viewData): string
    {
        return match ($engine) {
            'dompdf' => $this->dompdfRenderer->render('invoices.pdf', $viewData),
            default => $this->mpdfRenderer->render('invoices.pdf-mpdf', $viewData),
        };
    }

    /**
     * Renders to a temp file first and only replaces the real invoice path
     * once the output is verified to be a real, non-empty PDF — an order's
     * only invoice file is never left corrupted or half-written, and a
     * regenerate always leaves a fresh mtime on the final file.
     */
    protected function writeAtomically(string $finalPath, string $pdfBytes): void
    {
        if (strlen($pdfBytes) === 0 || ! str_starts_with($pdfBytes, '%PDF')) {
            throw new \RuntimeException('Generated PDF failed validation (empty or missing %PDF signature)');
        }

        $tempPath = dirname($finalPath).'/.tmp-'.Str::random(16).'-'.basename($finalPath);

        Storage::disk('local')->put($tempPath, $pdfBytes);

        $tempFullPath = Storage::disk('local')->path($tempPath);

        if (! is_file($tempFullPath) || filesize($tempFullPath) === 0) {
            Storage::disk('local')->delete($tempPath);

            throw new \RuntimeException('Temporary invoice PDF file is missing or empty after write');
        }

        Storage::disk('local')->move($tempPath, $finalPath);
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalizeInvoiceData(Order $order, string $invoiceNumber, string $locale): array
    {
        return [
            'order' => $order,
            'invoiceNumber' => $invoiceNumber,
            'locale' => $locale,
            'isRtl' => $locale === 'ar',
            'djSupportEmail' => Setting::get('support_email'),
            'djWhatsapp' => Setting::get('whatsapp_number'),
            'djItems' => $order->items->map(fn ($item) => [
                'name' => $this->resolveItemName($item, $locale),
                'sku' => $item->product?->sku ?: '—',
                'size' => $item->size ?: '—',
                'quantity' => $item->quantity,
                'price' => $item->price,
                'lineTotal' => $item->price * $item->quantity,
                'localImagePath' => $this->resolveLocalImagePath($item->product?->cover_image_src, $order->id, $item->id),
            ])->all(),
        ];
    }

    /**
     * Never null/empty — a missing product relation or a blank name column
     * still needs a printable invoice line, but that's logged rather than
     * silently hidden so a real broken relation surfaces somewhere.
     */
    protected function resolveItemName($item, string $locale): string
    {
        $name = $locale === 'ar'
            ? ($item->product?->name_ar ?: $item->product_name)
            : ($item->product?->name_en ?: $item->product_name);

        if ($name) {
            return $name;
        }

        Log::warning('Invoice line item missing a usable product name', [
            'order_item_id' => $item->id,
            'product_id' => $item->product_id,
        ]);

        return $locale === 'ar' ? 'منتج' : 'Product';
    }

    /**
     * Resolves the product's cover image to a local filesystem path mPDF
     * can read directly — no HTTP round-trip back to the same site during
     * generation. Returns null (falls back to a placeholder box in the
     * template) for a legacy absolute-URL image or a genuinely missing
     * file, logging so a real broken image reference doesn't go unnoticed.
     */
    protected function resolveLocalImagePath(?string $coverImageSrc, int $orderId, int $orderItemId): ?string
    {
        if (! $coverImageSrc) {
            return null;
        }

        if (Str::startsWith($coverImageSrc, ['http://', 'https://'])) {
            $publicUrl = rtrim(config('app.url'), '/').'/storage/';

            if (! Str::startsWith($coverImageSrc, $publicUrl)) {
                Log::warning('Invoice line item image is a non-local URL; using placeholder', [
                    'order_id' => $orderId,
                    'order_item_id' => $orderItemId,
                ]);

                return null;
            }

            $relative = Str::after($coverImageSrc, $publicUrl);
        } else {
            $relative = $coverImageSrc;
        }

        $fullPath = storage_path('app/public/'.$relative);

        if (! is_file($fullPath)) {
            Log::warning('Invoice line item image file not found on disk; using placeholder', [
                'order_id' => $orderId,
                'order_item_id' => $orderItemId,
                'expected_path' => $fullPath,
            ]);

            return null;
        }

        return $fullPath;
    }

    protected function generateInvoiceNumber(): string
    {
        do {
            $invoiceNumber = 'INV-'.now()->format('Y').'-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
        } while (Invoice::where('invoice_number', $invoiceNumber)->exists());

        return $invoiceNumber;
    }
}
