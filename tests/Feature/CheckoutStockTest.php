<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Notifications\NewOrderPlaced;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CheckoutStockTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProduct(int $stock): Product
    {
        $category = Category::create([
            'name_ar' => 'عبايات', 'name_en' => 'Abayas', 'slug' => 'abayas', 'is_active' => true, 'sort_order' => 1,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => 'عباية', 'name_en' => 'Abaya', 'slug' => 'abaya-'.uniqid(),
            'price' => 1000, 'is_active' => true, 'is_featured' => false,
        ]);

        $product->sizes()->create(['size' => 'M', 'stock' => $stock]);

        return $product;
    }

    protected function checkoutPayload(ShippingMethod $shippingMethod, User $user): array
    {
        return [
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '01000000000',
            'governorate' => 'Cairo',
            'city' => 'Nasr City',
            'address' => '123 Test St',
            'shipping_method_id' => (string) $shippingMethod->id,
            'payment_method' => Order::PAYMENT_METHOD_COD,
        ];
    }

    public function test_successful_order_decrements_stock_and_marks_deducted(): void
    {
        Notification::fake();
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $product = $this->makeProduct(5);
        $shippingMethod = ShippingMethod::create(['name_ar' => 'شحن', 'name_en' => 'Shipping', 'fee' => 50, 'estimated_days' => '2-3', 'is_active' => true]);

        $this->actingAs($user)->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 2])->assertOk();

        $response = $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($shippingMethod, $user));

        $order = Order::first();
        $response->assertRedirect(route('checkout.success', $order));

        $this->assertSame(3, $product->sizes()->where('size', 'M')->value('stock'));
        $this->assertNotNull($order->fresh()->stock_deducted_at);

        Notification::assertSentTo($admin, NewOrderPlaced::class);
    }

    public function test_order_is_rejected_when_stock_is_insufficient_at_checkout_time(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct(1);
        $shippingMethod = ShippingMethod::create(['name_ar' => 'شحن', 'name_en' => 'Shipping', 'fee' => 50, 'estimated_days' => '2-3', 'is_active' => true]);

        $this->actingAs($user)->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        // Stock changes after the item was added to cart (e.g. another order, or an admin correction).
        $product->sizes()->where('size', 'M')->update(['stock' => 0]);

        $response = $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($shippingMethod, $user));

        $response->assertSessionHasErrors('stock');
        $this->assertSame(0, Order::count());
        $this->assertSame(0, $product->sizes()->where('size', 'M')->value('stock'));
    }

    /**
     * Reproduces the exact failure this fix addresses: StockAlertService::
     * checkThreshold() runs inside CheckoutController's order-creation
     * DB::transaction(). Before the fix, a Notification::send() failure
     * there (e.g. a transient mail/queue problem while notifying admins
     * the item just sold out) would propagate out of the transaction,
     * rolling back an entire valid, already-stock-checked customer order.
     * Deliberately does NOT use Notification::fake() — that would swallow
     * the call before it could throw, and never actually exercise the
     * try/catch. Mocking the facade directly lets the real
     * StockAlertService code run and really throw.
     *
     * The mock only throws for ProductOutOfStock/ProductLowStock, not for
     * every notification indiscriminately: a real, logged-in checkout also
     * fires OrderPlaced/NewOrderPlaced (safely wrapped in dispatchSafely)
     * and, separately, CartTrackingService::markConverted()'s own
     * Notification::send(CartConvertedAdminNotification) call — which is
     * NOT wrapped in any try/catch and is a distinct bug from the one this
     * test targets. Throwing indiscriminately here would conflate the two;
     * scoping the throw to only the classes under test keeps this test
     * about the one thing it's meant to verify.
     */
    public function test_checkout_still_succeeds_when_the_stock_alert_notification_throws(): void
    {
        Notification::shouldReceive('send')->andReturnUsing(function ($notifiable, $notification) {
            if ($notification instanceof \App\Notifications\ProductOutOfStock || $notification instanceof \App\Notifications\ProductLowStock) {
                throw new \RuntimeException('Simulated notification transport failure');
            }
        });

        $user = User::factory()->create();
        // Stock 1, buying the last unit: before=1, after=0 — crosses into
        // out-of-stock, triggering the ProductOutOfStock admin alert that's
        // now mocked to throw.
        $product = $this->makeProduct(1);
        $shippingMethod = ShippingMethod::create(['name_ar' => 'شحن', 'name_en' => 'Shipping', 'fee' => 50, 'estimated_days' => '2-3', 'is_active' => true]);

        $this->actingAs($user)->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        $response = $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($shippingMethod, $user));

        // The order must be created and committed despite the notification
        // failure — not rolled back, not a generic "something went wrong" error.
        $order = Order::first();
        $this->assertNotNull($order, 'Order was rolled back when the stock-alert notification failed — the exact bug this fix addresses.');
        $response->assertRedirect(route('checkout.success', $order));
        $response->assertSessionDoesntHaveErrors();

        $this->assertSame(0, $product->sizes()->where('size', 'M')->value('stock'));
        $this->assertNotNull($order->stock_deducted_at);
    }

    /**
     * Same failure shape and same fix pattern as the stock-alert test
     * above, for a different call site: CartTrackingService::markConverted()
     * runs right after the order-creation transaction has already
     * committed, directly from CheckoutController (not wrapped in that
     * controller's own dispatchSafely() helper). Before the fix, a
     * Notification::send(CartConvertedAdminNotification) failure there
     * would surface as a failed checkout response even though the order
     * was already placed successfully. Scoped to only throw for
     * CartConvertedAdminNotification so this test isn't confounded by the
     * OrderPlaced/NewOrderPlaced notifications also fired in this flow
     * (which are safely wrapped and irrelevant to what's under test here).
     */
    public function test_checkout_still_succeeds_when_the_cart_converted_admin_notification_throws(): void
    {
        Notification::shouldReceive('send')->andReturnUsing(function ($notifiable, $notification) {
            if ($notification instanceof \App\Notifications\CartConvertedAdminNotification) {
                throw new \RuntimeException('Simulated notification transport failure');
            }
        });

        $user = User::factory()->create();
        $product = $this->makeProduct(5);
        $shippingMethod = ShippingMethod::create(['name_ar' => 'شحن', 'name_en' => 'Shipping', 'fee' => 50, 'estimated_days' => '2-3', 'is_active' => true]);

        $this->actingAs($user)->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        $response = $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($shippingMethod, $user));

        $order = Order::first();
        $this->assertNotNull($order, 'Order was rolled back or the response failed when the cart-converted admin notification failed — the exact bug this fix addresses.');
        $response->assertRedirect(route('checkout.success', $order));
        $response->assertSessionDoesntHaveErrors();

        // The cart must still be marked converted despite the notification
        // failure — the fix only isolates the notification call, not the
        // update() that precedes it.
        $this->assertSame('converted', $user->carts()->first()->status);
    }
}
