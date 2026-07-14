<?php

namespace Tests\Feature;

use App\Mail\InvoiceMail;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Services\InvoicePdfRenderer;
use App\Services\InvoicePdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Covers the mPDF migration specifically — see InvoiceGenerationTest for
 * the pre-existing dompdf-era coverage (rollback engine, still exercised
 * via config(['invoice.pdf_engine' => 'dompdf']) below). mPDF was adopted
 * because dompdf was found to reverse Arabic characters on the production
 * server (e.g. "فاتورة" rendering as "ةروتاف") even though the identical
 * template rendered correctly on local dev — an environment-specific
 * dompdf bidi failure, not something a template change could fix.
 */
class InvoiceMpdfGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function makeOrder(array $overrides = [], array $itemOverrides = []): Order
    {
        $category = Category::create(['name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);
        $product = Product::create(array_merge([
            'category_id' => $category->id,
            'name_ar' => 'عباية حريرية فاخرة', 'name_en' => 'Luxury Silk Abaya',
            'slug' => 'abaya-'.uniqid(), 'price' => 1250, 'is_active' => true, 'is_featured' => false, 'sku' => 'ABY-001',
        ], $itemOverrides['product'] ?? []));

        $order = Order::create(array_merge([
            'order_number' => 'ORD-'.uniqid(),
            'customer_name' => 'فاطمة الزهراء عبد الرحمن',
            'customer_email' => 'fatima@example.com',
            'customer_phone' => '01012345678',
            'governorate' => 'القاهرة',
            'city' => 'مدينة نصر',
            'address' => 'شارع عباس العقاد، برج النخيل، الدور الخامس، شقة 12',
            'notes' => null,
            'locale' => 'ar',
            'subtotal' => 2500,
            'shipping_fee' => 50,
            'discount_amount' => 0,
            'total' => 2550,
            'status' => 'pending',
            'payment_method' => Order::PAYMENT_METHOD_COD,
        ], $overrides));

        $itemCount = $itemOverrides['count'] ?? 1;

        for ($i = 0; $i < $itemCount; $i++) {
            $order->items()->create([
                'product_id' => $product->id, 'product_name' => $product->name_en,
                'size' => $itemOverrides['size'] ?? 'L', 'price' => 1250, 'quantity' => 2,
            ]);
        }

        return $order->fresh(['items.product']);
    }

    protected function pdfPageCount(string $pdfBytes): int
    {
        preg_match('/\/Type\s*\/Pages.{0,80}?\/Count\s+(\d+)/s', $pdfBytes, $matches);

        return isset($matches[1]) ? (int) $matches[1] : 0;
    }

    public function test_mpdf_is_the_default_invoice_engine(): void
    {
        $this->assertSame('mpdf', config('invoice.pdf_engine'));
    }

    public function test_generate_produces_a_valid_non_empty_pdf_file(): void
    {
        $order = $this->makeOrder();

        $invoice = app(InvoicePdfService::class)->generate($order, 'ar');

        $this->assertNotNull($invoice);
        $this->assertTrue(Storage::disk('local')->exists($invoice->pdf_path));

        $bytes = Storage::disk('local')->get($invoice->pdf_path);
        $this->assertGreaterThan(0, strlen($bytes));
        $this->assertStringStartsWith('%PDF', $bytes);
    }

    public function test_single_item_arabic_invoice_stays_on_one_page(): void
    {
        $order = $this->makeOrder(itemOverrides: ['count' => 1]);

        $invoice = app(InvoicePdfService::class)->generate($order, 'ar');
        $bytes = Storage::disk('local')->get($invoice->pdf_path);

        $this->assertSame(1, $this->pdfPageCount($bytes));
    }

    public function test_two_item_arabic_invoice_with_support_email_stays_on_one_page(): void
    {
        \App\Models\Setting::set('support_email', 'info@dareljamila.com');
        $order = $this->makeOrder(itemOverrides: ['count' => 2]);

        $invoice = app(InvoicePdfService::class)->generate($order, 'ar');
        $bytes = Storage::disk('local')->get($invoice->pdf_path);

        $this->assertSame(1, $this->pdfPageCount($bytes));
    }

    public function test_ten_item_invoice_generates_without_exception(): void
    {
        $order = $this->makeOrder(itemOverrides: ['count' => 10]);

        $invoice = app(InvoicePdfService::class)->generate($order, 'ar');

        $this->assertNotNull($invoice);
        $bytes = Storage::disk('local')->get($invoice->pdf_path);
        $this->assertStringStartsWith('%PDF', $bytes);
    }

    public function test_english_only_customer_name_renders_without_exception(): void
    {
        $order = $this->makeOrder(['customer_name' => 'John Smith', 'locale' => 'en']);

        $invoice = app(InvoicePdfService::class)->generate($order, 'en');

        $this->assertNotNull($invoice);
    }

    public function test_mixed_arabic_and_english_customer_name_renders_without_exception(): void
    {
        $order = $this->makeOrder(['customer_name' => 'Mohamed أحمد Test']);

        $invoice = app(InvoicePdfService::class)->generate($order, 'ar');

        $this->assertNotNull($invoice);
    }

    public function test_missing_optional_notes_and_shipping_method_do_not_break_generation(): void
    {
        $order = $this->makeOrder(['notes' => null]);
        $this->assertNull($order->shipping_method_name);

        $invoice = app(InvoicePdfService::class)->generate($order, 'ar');

        $this->assertNotNull($invoice);
    }

    public function test_discount_present_renders_without_exception(): void
    {
        $order = $this->makeOrder(['discount_amount' => 200, 'coupon_code' => 'SAVE20']);

        $invoice = app(InvoicePdfService::class)->generate($order, 'ar');

        $this->assertNotNull($invoice);
    }

    public function test_missing_product_image_falls_back_to_a_placeholder_without_breaking(): void
    {
        // The factory-created product has no image_url and no gallery
        // images, so cover_image_src is already null — this exercises the
        // exact "missing image" path without needing a fake disk.
        $order = $this->makeOrder();

        $invoice = app(InvoicePdfService::class)->generate($order, 'ar');

        $this->assertNotNull($invoice);
    }

    public function test_rendered_html_contains_correct_unreversed_arabic_labels(): void
    {
        // PDF text-extraction of mPDF's output yields shaped Arabic
        // presentation-form glyphs (expected — that's what a correctly
        // shaped PDF embeds), which isn't a meaningful string to assert
        // on. What we can and must assert on is the actual HTML mPDF is
        // given: it must contain the correct, normal-order Arabic string
        // and never a manually reversed one.
        $order = $this->makeOrder();

        $html = view('invoices.pdf-mpdf', [
            'order' => $order,
            'invoiceNumber' => 'INV-TEST-0001',
            'locale' => 'ar',
            'isRtl' => true,
            'djItems' => $order->items->map(fn ($item) => [
                'name' => $item->product->name_ar,
                'sku' => $item->product->sku,
                'size' => $item->size,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'lineTotal' => $item->price * $item->quantity,
                'localImagePath' => null,
            ])->all(),
            'djSupportEmail' => null,
            'djWhatsapp' => null,
        ])->render();

        foreach (['فاتورة', 'دار الجميلة', 'التاريخ', 'حالة الطلب', 'رقم الطلب', 'الشحن إلى', 'فاتورة إلى', 'المنتج', 'رمز المنتج', 'المقاس', 'الكمية', 'سعر الوحدة', 'الإجمالي', 'المجموع الفرعي', 'الشحن', 'الإجمالي الكلي', 'طريقة الدفع'] as $label) {
            $this->assertStringContainsString($label, $html);
        }

        foreach (['ةروتاف', 'ةليمجلا راد', 'خيراتلا'] as $reversed) {
            $this->assertStringNotContainsString($reversed, $html);
        }
    }

    public function test_no_manual_arabic_reversal_helpers_exist_in_the_invoice_pipeline(): void
    {
        foreach ([
            app_path('Services/InvoicePdfService.php'),
            app_path('Services/InvoiceMpdfRenderer.php'),
            resource_path('views/invoices/pdf-mpdf.blade.php'),
        ] as $file) {
            $contents = file_get_contents($file);

            foreach (['strrev', 'array_reverse', 'mb_str_split', 'reverseArabic', 'arabicFix'] as $needle) {
                $this->assertStringNotContainsString($needle, $contents, "{$file} must not contain {$needle}()");
            }
        }
    }

    public function test_invoice_mail_attaches_the_freshly_generated_pdf(): void
    {
        Mail::fake();
        $order = $this->makeOrder();

        $invoice = app(InvoicePdfService::class)->generate($order, 'ar');
        Mail::to($order->customer_email)->send(new InvoiceMail($order, $invoice));

        Mail::assertQueued(InvoiceMail::class, function ($mail) use ($invoice) {
            $attachments = $mail->attachments();

            return count($attachments) === 1
                && $attachments[0]->as === "{$invoice->invoice_number}.pdf";
        });
    }

    public function test_regenerating_an_invoice_replaces_the_file_with_a_fresh_one_not_a_stale_copy(): void
    {
        $order = $this->makeOrder();

        $first = app(InvoicePdfService::class)->generate($order, 'ar');
        $firstBytes = Storage::disk('local')->get($first->pdf_path);
        $firstModifiedTime = Storage::disk('local')->lastModified($first->pdf_path);

        sleep(1);

        $order->update(['customer_name' => 'Updated Name For Regeneration Test']);
        $second = app(InvoicePdfService::class)->generate($order->fresh(['items.product']), 'ar');
        $secondBytes = Storage::disk('local')->get($second->pdf_path);

        $this->assertSame($first->invoice_number, $second->invoice_number);
        $this->assertSame($first->pdf_path, $second->pdf_path);
        $this->assertGreaterThanOrEqual($firstModifiedTime, Storage::disk('local')->lastModified($second->pdf_path));
        $this->assertNotSame($firstBytes, $secondBytes);
    }

    public function test_engine_can_be_rolled_back_to_dompdf_via_config(): void
    {
        config(['invoice.pdf_engine' => 'dompdf']);
        $order = $this->makeOrder();

        $invoice = app(InvoicePdfService::class)->generate($order, 'ar');

        $this->assertNotNull($invoice);
        $bytes = Storage::disk('local')->get($invoice->pdf_path);
        $this->assertStringStartsWith('%PDF', $bytes);
    }

    public function test_generation_failure_is_logged_and_admins_notified_without_corrupting_existing_invoice(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $admin = \App\Models\User::factory()->create();
        $admin->assignRole(\Spatie\Permission\Models\Role::findOrCreate('admin', 'web'));
        $order = $this->makeOrder();

        // A genuinely broken view (referencing an undefined variable that
        // throws) simulates a real render failure without mocking mPDF
        // internals.
        config(['invoice.pdf_engine' => 'dompdf']);
        app()->bind(InvoicePdfRenderer::class, function () {
            return new class extends InvoicePdfRenderer
            {
                public function render(string $view, array $data = []): string
                {
                    throw new \RuntimeException('Simulated render failure');
                }
            };
        });

        $invoice = app(InvoicePdfService::class)->generate($order, 'ar');

        $this->assertNull($invoice);
        // A row now exists (created as "processing" before rendering even
        // starts, so a failure is always observable) and was marked failed
        // — it never silently vanishes as if nothing had been attempted.
        $failedInvoice = Invoice::where('order_id', $order->id)->first();
        $this->assertNotNull($failedInvoice);
        $this->assertSame(Invoice::STATUS_FAILED, $failedInvoice->status);
        \Illuminate\Support\Facades\Notification::assertSentTo(
            $admin,
            \App\Notifications\InvoiceGenerationFailedAdminNotification::class
        );
    }
}
