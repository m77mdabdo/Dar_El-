<?php

namespace Tests\Feature;

use App\Jobs\GenerateAndSendInvoice;
use App\Mail\InvoiceMail;
use App\Models\Category;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\NewOrderPlaced;
use App\Notifications\OrderPlaced;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CheckoutEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProduct(int $stock = 5): Product
    {
        $category = Category::create([
            'name_ar' => 'عبايات', 'name_en' => 'Abayas', 'slug' => 'abayas-'.uniqid(), 'is_active' => true, 'sort_order' => 1,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => 'عباية', 'name_en' => 'Abaya', 'slug' => 'abaya-'.uniqid(),
            'price' => 1000, 'is_active' => true, 'is_featured' => false,
        ]);

        $product->sizes()->create(['size' => 'M', 'stock' => $stock]);

        return $product;
    }

    protected function checkoutPayload(string $email, string $name, array $overrides = []): array
    {
        return array_merge([
            'customer_name' => $name,
            'customer_email' => $email,
            'customer_phone' => '01000000000',
            'governorate' => 'Cairo',
            'city' => 'Nasr City',
            'address' => '123 Test St',
            'shipping_method_id' => 'standard',
            'payment_method' => Order::PAYMENT_METHOD_COD,
        ], $overrides);
    }

    protected function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create(['email' => 'admin@dar-el-jamila.test']);
        $admin->assignRole('admin');

        return $admin;
    }

    protected function placeOrder(User $user, array $payloadOverrides = []): Order
    {
        $product = $this->makeProduct();
        $this->actingAs($user)->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        $this->actingAs($user)->post(
            route('checkout.store'),
            $this->checkoutPayload($payloadOverrides['customer_email'] ?? $user->email, $user->name, $payloadOverrides)
        );

        return Order::latest('id')->firstOrFail();
    }

    public function test_registered_customer_receives_order_confirmation_email(): void
    {
        Mail::fake();
        $this->admin();
        $user = User::factory()->create(['email' => 'customer@example.com']);

        $order = $this->placeOrder($user);

        Mail::assertQueued(InvoiceMail::class, fn ($mail) => $mail->hasTo('customer@example.com') && $mail->order->is($order) && $mail->invoice === null);
    }

    public function test_guest_customer_receives_order_confirmation_using_checkout_email(): void
    {
        // Data-layer-only version of this check: builds the guest Order
        // directly rather than through the real HTTP checkout flow, to
        // isolate "does the resolver/dispatch work purely from the order's
        // own email snapshot, without touching a user relation that
        // doesn't exist" from everything else checkout does. See
        // test_guest_customer_receives_order_confirmation_via_the_real_checkout_flow_below
        // for the full HTTP-driven equivalent (guest checkout is a real,
        // unauthenticated path now — see GuestCheckoutTest).
        Mail::fake();

        $order = Order::create([
            'order_number' => 'ORD-GUEST-'.uniqid(),
            'user_id' => null,
            'customer_name' => 'Guest Shopper',
            'customer_email' => 'guest-checkout@example.com',
            'customer_phone' => '01000000000',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => 'Street 1',
            'locale' => 'en', 'subtotal' => 100, 'shipping_fee' => 0, 'discount_amount' => 0, 'total' => 100,
            'status' => 'pending', 'payment_method' => Order::PAYMENT_METHOD_COD,
        ]);

        $this->assertSame('guest-checkout@example.com', $order->resolveCustomerEmail());

        Mail::to($order->resolveCustomerEmail())->send(new InvoiceMail($order));

        Mail::assertQueued(InvoiceMail::class, fn ($mail) => $mail->hasTo('guest-checkout@example.com'));
    }

    public function test_guest_customer_receives_order_confirmation_via_the_real_checkout_flow(): void
    {
        Mail::fake();
        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        // Guest checkout requires a CAPTCHA answer (see
        // CheckoutController::generateCaptcha()) — visiting show() first
        // seeds the session with the challenge this submission answers.
        $this->get(route('checkout.show'));
        $this->post(route('checkout.store'), $this->checkoutPayload('real-guest@example.com', 'Real Guest', [
            'customer_email' => 'real-guest@example.com',
            'captcha_answer' => session('checkout_captcha_answer'),
        ]))->assertSessionHasNoErrors();

        $order = Order::latest('id')->firstOrFail();
        $this->assertNull($order->user_id);

        Mail::assertQueued(InvoiceMail::class, fn ($mail) => $mail->hasTo('real-guest@example.com') && $mail->order->is($order));
    }

    public function test_guest_checkout_without_an_email_skips_the_confirmation_email_without_erroring(): void
    {
        Mail::fake();
        $product = $this->makeProduct();
        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        $this->get(route('checkout.show'));
        $response = $this->post(route('checkout.store'), $this->checkoutPayload('', 'No Email Guest', [
            'customer_email' => null,
            'captcha_answer' => session('checkout_captcha_answer'),
        ]));

        $response->assertSessionHasNoErrors();
        Mail::assertNothingQueued();
    }

    public function test_admin_receives_separate_new_order_notification(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $user = User::factory()->create(['email' => 'customer2@example.com']);

        $order = $this->placeOrder($user);

        Notification::assertSentTo($admin, NewOrderPlaced::class, fn ($notification) => $notification->order->is($order));
    }

    public function test_customer_does_not_receive_admin_notification(): void
    {
        Notification::fake();
        $this->admin();
        $user = User::factory()->create(['email' => 'customer3@example.com']);

        $this->placeOrder($user);

        Notification::assertNotSentTo($user, NewOrderPlaced::class);
    }

    public function test_admin_does_not_receive_customer_confirmation_by_mistake(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $user = User::factory()->create(['email' => 'customer4@example.com']);

        $this->placeOrder($user);

        Mail::assertQueued(InvoiceMail::class, fn ($mail) => ! $mail->hasTo($admin->email));
    }

    public function test_customer_confirmation_uses_order_email_snapshot_not_the_authenticated_account_email(): void
    {
        // A logged-in customer can submit a different email at checkout
        // than the one on their account (e.g. ordering for someone else,
        // or an account email that's simply out of date). The confirmation
        // must follow the submitted checkout email, never the session's
        // authenticated user email.
        Mail::fake();
        $this->admin();
        $user = User::factory()->create(['email' => 'account-email@example.com']);

        $order = $this->placeOrder($user, ['customer_email' => 'different-checkout-email@example.com']);

        $this->assertSame('different-checkout-email@example.com', $order->customer_email);
        Mail::assertQueued(InvoiceMail::class, fn ($mail) => $mail->hasTo('different-checkout-email@example.com') && ! $mail->hasTo('account-email@example.com'));
    }

    public function test_registered_customer_receives_order_placed_database_notification(): void
    {
        $this->admin();
        $user = User::factory()->create(['email' => 'customer5@example.com']);

        $order = $this->placeOrder($user);

        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $user->id,
            'type' => OrderPlaced::class,
        ]);
        $this->assertSame($order->id, $user->fresh()->notifications->first()->data['order_id']);
    }

    public function test_invoice_ready_email_is_sent_to_the_resolved_customer_email_after_generation_succeeds(): void
    {
        Mail::fake();

        $order = Order::create([
            'order_number' => 'ORD-INV-'.uniqid(),
            'customer_name' => 'Test Customer', 'customer_email' => 'invoice-ready@example.com', 'customer_phone' => '0100',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => 'Street 1',
            'locale' => 'en', 'subtotal' => 100, 'shipping_fee' => 0, 'discount_amount' => 0, 'total' => 100,
            'status' => 'pending', 'payment_method' => Order::PAYMENT_METHOD_COD,
        ]);

        GenerateAndSendInvoice::dispatchSync($order);

        $this->assertSame(1, Invoice::where('order_id', $order->id)->count());
        Mail::assertQueued(InvoiceMail::class, fn ($mail) => $mail->hasTo('invoice-ready@example.com') && $mail->invoice !== null);
    }

    public function test_missing_customer_email_is_logged_and_does_not_break_invoice_dispatch(): void
    {
        Mail::fake();

        $order = Order::create([
            'order_number' => 'ORD-NOEMAIL-'.uniqid(),
            'user_id' => null,
            'customer_name' => 'No Email Customer', 'customer_email' => '', 'customer_phone' => '0100',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => 'Street 1',
            'locale' => 'en', 'subtotal' => 100, 'shipping_fee' => 0, 'discount_amount' => 0, 'total' => 100,
            'status' => 'pending', 'payment_method' => Order::PAYMENT_METHOD_COD,
        ]);

        $this->assertNull($order->resolveCustomerEmail());

        // The job must not throw even though there is nothing to email.
        GenerateAndSendInvoice::dispatchSync($order);

        Mail::assertNothingQueued();
        $this->assertSame(1, Invoice::where('order_id', $order->id)->count());
    }

    public function test_order_creation_succeeds_even_if_customer_email_dispatch_fails(): void
    {
        $this->admin();
        $user = User::factory()->create(['email' => 'customer6@example.com']);

        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP unreachable (simulated)'));

        $product = $this->makeProduct();
        $this->actingAs($user)->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        $response = $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($user->email, $user->name));

        $order = Order::latest('id')->first();
        $response->assertRedirect(route('checkout.success', $order));
        $this->assertNotNull($order);
        $this->assertSame($user->email, $order->customer_email);
    }
}
