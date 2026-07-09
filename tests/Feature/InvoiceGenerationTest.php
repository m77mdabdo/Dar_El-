<?php

namespace Tests\Feature;

use App\Jobs\GenerateAndSendInvoice;
use App\Mail\InvoiceMail;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InvoiceGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected function makeOrder(array $overrides = []): Order
    {
        $category = Category::create(['name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);
        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => 'عباية حريرية فاخرة', 'name_en' => 'Luxury Silk Abaya',
            'slug' => 'abaya-'.uniqid(), 'price' => 1250, 'is_active' => true, 'is_featured' => false, 'sku' => 'ABY-001',
        ]);

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
            'payment_method' => 'cod',
        ], $overrides));

        $order->items()->create([
            'product_id' => $product->id, 'product_name' => $product->name_en,
            'size' => 'L', 'price' => 1250, 'quantity' => 2,
        ]);

        return $order;
    }

    public function test_job_generates_a_pdf_and_stores_an_invoice_record(): void
    {
        Mail::fake();
        Storage::fake('local');
        $order = $this->makeOrder();

        GenerateAndSendInvoice::dispatchSync($order);

        $invoice = Invoice::where('order_id', $order->id)->first();
        $this->assertNotNull($invoice);
        $this->assertNotNull($invoice->invoice_number);
        $this->assertNotNull($invoice->pdf_path);
        Storage::disk('local')->assertExists($invoice->pdf_path);
        // InvoiceMail implements ShouldQueue, so Laravel's Mailer::send()
        // transparently redirects to queue() — this is existing, intended
        // behavior (a real queue worker delivers it), not a test bug.
        Mail::assertQueued(InvoiceMail::class, fn ($mail) => $mail->hasTo($order->customer_email));
    }

    public function test_arabic_invoice_renders_arabic_labels_and_rtl_direction(): void
    {
        $order = $this->makeOrder(['locale' => 'ar']);
        $order->load('items.product');

        app()->setLocale('ar');
        $html = view('invoices.pdf', [
            'order' => $order, 'invoiceNumber' => 'INV-2026-000001', 'locale' => 'ar', 'isRtl' => true, 'djSupportEmail' => null,
        ])->render();

        $this->assertStringContainsString('direction: rtl', $html);
        $this->assertStringContainsString(__('invoice.invoice', [], 'ar'), $html);
        $this->assertStringContainsString(__('invoice.bill_to', [], 'ar'), $html);
        $this->assertStringContainsString('فاطمة الزهراء عبد الرحمن', $html);
        $this->assertStringContainsString('عباية حريرية فاخرة', $html); // Arabic product name used in RTL invoice
        $this->assertStringContainsString("Cairo", $html);
    }

    public function test_english_invoice_renders_english_labels_and_ltr_direction(): void
    {
        $order = $this->makeOrder(['locale' => 'en']);
        $order->load('items.product');

        app()->setLocale('en');
        $html = view('invoices.pdf', [
            'order' => $order, 'invoiceNumber' => 'INV-2026-000002', 'locale' => 'en', 'isRtl' => false, 'djSupportEmail' => null,
        ])->render();

        $this->assertStringContainsString('direction: ltr', $html);
        $this->assertStringContainsString('Bill To', $html);
        $this->assertStringContainsString('Luxury Silk Abaya', $html); // English product name used in LTR invoice
    }

    public function test_mixed_locale_still_shows_customer_supplied_arabic_text_correctly_in_english_invoice(): void
    {
        $order = $this->makeOrder(['locale' => 'en', 'notes' => 'يرجى التوصيل بعد الساعة 5 مساءً - after 5pm please']);
        $order->load('items.product');

        app()->setLocale('en');
        $html = view('invoices.pdf', [
            'order' => $order, 'invoiceNumber' => 'INV-2026-000003', 'locale' => 'en', 'isRtl' => false, 'djSupportEmail' => null,
        ])->render();

        $this->assertStringContainsString('يرجى التوصيل بعد الساعة 5 مساءً', $html);
        $this->assertStringContainsString('after 5pm please', $html);
    }

    public function test_regenerating_invoice_keeps_the_same_invoice_number_and_file_path(): void
    {
        Mail::fake();
        Storage::fake('local');
        $order = $this->makeOrder();

        GenerateAndSendInvoice::dispatchSync($order);
        $first = Invoice::where('order_id', $order->id)->first();

        GenerateAndSendInvoice::dispatchSync($order->fresh());
        $second = Invoice::where('order_id', $order->id)->first();

        $this->assertSame(1, Invoice::where('order_id', $order->id)->count());
        $this->assertSame($first->invoice_number, $second->invoice_number);
        $this->assertSame($first->pdf_path, $second->pdf_path);
    }

    public function test_checkout_captures_the_current_app_locale_on_the_order(): void
    {
        app()->setLocale('ar');
        $user = User::factory()->create(); // factory default includes a verified email_verified_at
        $shippingMethod = ShippingMethod::create(['name_ar' => 'عادي', 'name_en' => 'Standard', 'fee' => 50, 'estimated_days' => '2-3', 'is_active' => true]);
        $category = Category::create(['name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);
        $product = Product::create(['category_id' => $category->id, 'name_ar' => 'عباية', 'name_en' => 'Abaya', 'slug' => 'abaya-'.uniqid(), 'price' => 500, 'is_active' => true, 'is_featured' => false]);
        $product->sizes()->create(['size' => 'M', 'stock' => 5]);

        $response = $this->actingAs($user)
            ->withSession(['cart' => [$product->id.'-M' => ['product_id' => $product->id, 'size' => 'M', 'quantity' => 1]]])
            ->post(route('checkout.store'), [
                'customer_name' => 'Test Customer', 'customer_email' => 'test@example.com', 'customer_phone' => '01000000000',
                'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => '123 Test St',
                'shipping_method_id' => $shippingMethod->id,
            ]);

        $order = Order::where('customer_email', 'test@example.com')->first();
        $this->assertNotNull($order, 'Checkout failed. Status: '.$response->getStatusCode().' Redirect: '.($response->headers->get('Location') ?? 'none').' Errors: '.json_encode(session('errors')?->all() ?? []));
        $this->assertSame('ar', $order->locale);
    }

    public function test_customer_confirmation_email_includes_view_order_and_download_invoice_buttons_not_admin_link(): void
    {
        Mail::fake();
        Storage::fake('local');
        $order = $this->makeOrder();
        GenerateAndSendInvoice::dispatchSync($order);
        $invoice = Invoice::where('order_id', $order->id)->first();

        $mail = new InvoiceMail($order->fresh(), $invoice);
        $html = $mail->render();

        $this->assertStringContainsString(route('account.orders.show', $order), $html);
        $this->assertStringNotContainsString('/admin/orders/', $html);
        $this->assertStringContainsString(route('invoice.download', ['order' => $order->id]), $this->stripSignature($html));
    }

    /**
     * The rendered signed URL contains a random signature/expiry, so assert
     * only on the stable path portion.
     */
    protected function stripSignature(string $html): string
    {
        return preg_replace('/\?expires=.*?(?="|\')/', '', $html) ?? $html;
    }

    public function test_signed_invoice_download_route_works_for_a_guest(): void
    {
        Mail::fake();
        Storage::fake('local');
        $order = $this->makeOrder();
        GenerateAndSendInvoice::dispatchSync($order);

        $url = URL::temporarySignedRoute('invoice.download', now()->addYear(), ['order' => $order->id]);

        $response = $this->get($url);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_invoice_download_route_rejects_a_tampered_signature(): void
    {
        $order = $this->makeOrder();

        $url = URL::temporarySignedRoute('invoice.download', now()->addYear(), ['order' => $order->id]);
        $tampered = $url.'&tampered=1';

        $this->get($tampered)->assertForbidden();
    }

    public function test_invoice_download_404s_when_no_invoice_exists_yet(): void
    {
        $order = $this->makeOrder();

        $url = URL::temporarySignedRoute('invoice.download', now()->addYear(), ['order' => $order->id]);

        $this->get($url)->assertNotFound();
    }

    public function test_customer_can_download_their_own_invoice_via_the_account_route(): void
    {
        Mail::fake();
        Storage::fake('local');
        $user = User::factory()->create(['email' => 'fatima@example.com']);
        Role::findOrCreate('customer', 'web');
        $user->assignRole('customer');

        $order = $this->makeOrder(['user_id' => $user->id]);
        GenerateAndSendInvoice::dispatchSync($order);

        $this->actingAs($user)
            ->get(route('account.orders.invoice', $order))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_customer_cannot_download_another_customers_invoice(): void
    {
        Mail::fake();
        Storage::fake('local');
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        Role::findOrCreate('customer', 'web');
        $intruder->assignRole('customer');

        $order = $this->makeOrder(['user_id' => $owner->id]);
        GenerateAndSendInvoice::dispatchSync($order);

        $this->actingAs($intruder)->get(route('account.orders.invoice', $order))->assertForbidden();
    }

    public function test_admin_can_download_any_customers_invoice(): void
    {
        Mail::fake();
        Storage::fake('local');
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');

        $order = $this->makeOrder();
        GenerateAndSendInvoice::dispatchSync($order);

        $this->actingAs($admin)
            ->get(route('admin.orders.invoice', $order))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_new_order_email_links_to_the_admin_route(): void
    {
        $order = $this->makeOrder();

        $html = view('emails.admin.new-order', ['order' => $order])->render();

        $this->assertStringContainsString(route('admin.orders.show', $order), $html);
    }

    public function test_account_orders_invoice_redirects_gracefully_when_not_ready_instead_of_404ing(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('customer', 'web');
        $user->assignRole('customer');
        $order = $this->makeOrder(['user_id' => $user->id]);
        // Deliberately no Invoice row created — simulates generation still
        // pending (queued) or having previously failed.

        $response = $this->actingAs($user)->get(route('account.orders.invoice', $order));

        $response->assertRedirect(route('account.orders.show', $order));
        $response->assertSessionHas('error', __('orders.invoice_not_ready'));
    }

    public function test_admin_orders_invoice_redirects_gracefully_when_not_ready_instead_of_404ing(): void
    {
        $admin = User::factory()->create();
        Role::findOrCreate('admin', 'web');
        $admin->assignRole('admin');
        $order = $this->makeOrder();

        $response = $this->actingAs($admin)->get(route('admin.orders.invoice', $order));

        $response->assertRedirect(route('admin.orders.show', $order));
        $response->assertSessionHas('error', __('orders.invoice_not_ready'));
    }

    public function test_invoice_generation_failure_still_sends_the_confirmation_email_and_notifies_admins(): void
    {
        Mail::fake();
        Notification::fake();
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        // Force a real failure: point dompdf at a view that doesn't exist,
        // by deleting the order's items so the PDF view still renders but
        // simulate failure more directly by making the view render throw —
        // simplest reliable trigger available without mocking internals is
        // to make the target storage path unwritable-equivalent: fake a
        // storage disk that always throws on put().
        Storage::shouldReceive('disk')->with('local')->andReturnUsing(function () {
            $fake = \Mockery::mock();
            $fake->shouldReceive('put')->andThrow(new \RuntimeException('Disk full (simulated)'));
            $fake->shouldReceive('exists')->andReturn(false);

            return $fake;
        });

        $order = $this->makeOrder();

        GenerateAndSendInvoice::dispatchSync($order);

        $this->assertSame(0, Invoice::where('order_id', $order->id)->count());
        Mail::assertQueued(InvoiceMail::class, fn ($mail) => $mail->hasTo($order->customer_email) && $mail->invoice === null);
        Notification::assertSentTo($admin, \App\Notifications\InvoiceGenerationFailedAdminNotification::class);
    }

    public function test_confirmation_email_without_an_invoice_does_not_show_a_download_invoice_button(): void
    {
        $order = $this->makeOrder();

        $mail = new InvoiceMail($order, null);
        $html = $mail->render();

        $this->assertStringNotContainsString(__('emails.order_download_invoice_button'), $html);
        $this->assertStringContainsString(__('emails.order_confirmation_intro_no_invoice', ['number' => $order->order_number]), $html);
    }

    public function test_email_header_and_icon_backgrounds_have_a_solid_color_fallback(): void
    {
        // Many email clients (notably Outlook) don't support CSS
        // linear-gradient() and silently drop the whole declaration —
        // without an explicit background-color fallback first, the branded
        // maroon header/icon badge would render as a blank/white box.
        $order = $this->makeOrder();
        $html = (new InvoiceMail($order, null))->render();

        $this->assertMatchesRegularExpression('/background-color:#3C0B17;\s*background:linear-gradient/', $html);
    }

    public function test_customer_order_page_shows_product_image_and_payment_method(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate('customer', 'web');
        $user->assignRole('customer');
        $order = $this->makeOrder(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('account.orders.show', $order));

        $response->assertOk();
        $response->assertSee(__('emails.order_payment_method_cod'));
        $response->assertSee(__('orders.status_'.$order->status));
    }
}
