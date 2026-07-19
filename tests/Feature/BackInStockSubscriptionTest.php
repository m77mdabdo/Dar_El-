<?php

namespace Tests\Feature;

use App\Mail\ProductBackInStockMail;
use App\Models\BackInStockSubscription;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\User;
use App\Services\BackInStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BackInStockSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected ?Category $defaultCategory = null;

    protected function defaultCategory(): Category
    {
        return $this->defaultCategory ??= Category::create([
            'name_ar' => 'General', 'name_en' => 'General', 'slug' => 'general-'.uniqid(),
            'is_active' => true, 'sort_order' => 1,
        ]);
    }

    protected function makeProduct(string $name = 'Product'): Product
    {
        return Product::create([
            'category_id' => $this->defaultCategory()->id,
            'name_ar' => $name, 'name_en' => $name, 'slug' => 'product-'.uniqid(),
            'price' => 1000, 'is_active' => true, 'is_featured' => false,
        ]);
    }

    protected function makeAdmin(): User
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    // ---------------------------------------------------------------
    // Signup
    // ---------------------------------------------------------------

    public function test_guest_signup_creates_a_subscription_record(): void
    {
        $product = $this->makeProduct();
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 0]);

        $response = $this->postJson(route('back-in-stock.store', $product), [
            'email' => 'guest@example.com',
            'product_size_id' => $size->id,
        ]);

        $response->assertOk();
        $response->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('back_in_stock_subscriptions', [
            'product_id' => $product->id,
            'product_size_id' => $size->id,
            'email' => 'guest@example.com',
            'user_id' => null,
        ]);
    }

    public function test_logged_in_signup_records_the_user_id(): void
    {
        $product = $this->makeProduct();
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 0]);
        $user = User::factory()->create(['email' => 'customer@example.com']);

        $response = $this->actingAs($user)->postJson(route('back-in-stock.store', $product), [
            'email' => 'customer@example.com',
            'product_size_id' => $size->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('back_in_stock_subscriptions', [
            'product_id' => $product->id,
            'product_size_id' => $size->id,
            'email' => 'customer@example.com',
            'user_id' => $user->id,
        ]);
    }

    public function test_duplicate_signup_is_handled_gracefully_not_as_an_error(): void
    {
        $product = $this->makeProduct();
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 0]);

        $first = $this->postJson(route('back-in-stock.store', $product), [
            'email' => 'repeat@example.com', 'product_size_id' => $size->id,
        ]);
        $first->assertOk();

        $second = $this->postJson(route('back-in-stock.store', $product), [
            'email' => 'repeat@example.com', 'product_size_id' => $size->id,
        ]);

        $second->assertOk();
        $second->assertJson(['status' => 'ok']);
        // Default locale is Arabic, so __() resolves to the Arabic string.
        $this->assertSame(
            __("You're already on the list for this — we'll email you the moment it's back."),
            $second->json('message')
        );

        $this->assertSame(1, BackInStockSubscription::where('product_id', $product->id)
            ->where('product_size_id', $size->id)
            ->where('email', 'repeat@example.com')
            ->count());
    }

    /**
     * Regression guard: a subscriber who was already notified once (e.g. an
     * earlier restock) and resubscribes after a later stock-out must be
     * re-armed for the next crossing, not silently excluded forever —
     * BackInStockService only ever queries whereNull('notified_at'), so
     * leaving the old row untouched would permanently lock them out while
     * this endpoint told them they were "already on the list."
     */
    public function test_resubscribing_after_already_notified_re_arms_the_subscription(): void
    {
        Mail::fake();

        $product = $this->makeProduct();
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 0]);
        $subscription = BackInStockSubscription::create([
            'product_id' => $product->id, 'product_size_id' => $size->id, 'email' => 'repeat-customer@example.com',
            'notified_at' => now()->subMonth(),
        ]);

        $response = $this->postJson(route('back-in-stock.store', $product), [
            'email' => 'repeat-customer@example.com', 'product_size_id' => $size->id,
        ]);

        $response->assertOk();
        // Same row is reused (no duplicate), but re-armed for the next crossing.
        $this->assertSame(1, BackInStockSubscription::where('product_id', $product->id)
            ->where('product_size_id', $size->id)
            ->where('email', 'repeat-customer@example.com')
            ->count());
        $this->assertNull($subscription->fresh()->notified_at);

        // Prove it's actually functional again, not just cleared in isolation.
        app(BackInStockService::class)->checkAndNotify($product, $size, before: 0, after: 5);

        Mail::assertQueued(ProductBackInStockMail::class, fn ($mail) => $mail->hasTo('repeat-customer@example.com'));
    }

    public function test_signup_for_a_multi_size_product_is_size_specific(): void
    {
        $product = $this->makeProduct();
        $sizeM = $product->sizes()->create(['size' => 'M', 'stock' => 0]);
        $product->sizes()->create(['size' => 'L', 'stock' => 5]);

        $this->postJson(route('back-in-stock.store', $product), [
            'email' => 'sizefan@example.com', 'product_size_id' => $sizeM->id,
        ])->assertOk();

        $this->assertDatabaseHas('back_in_stock_subscriptions', [
            'product_id' => $product->id, 'product_size_id' => $sizeM->id, 'email' => 'sizefan@example.com',
        ]);
    }

    public function test_signup_for_a_whole_product_uses_a_null_size(): void
    {
        // No sizes at all — the "product has no sizes" whole-product case.
        $product = $this->makeProduct();

        $this->postJson(route('back-in-stock.store', $product), [
            'email' => 'wholeproduct@example.com',
        ])->assertOk();

        $this->assertDatabaseHas('back_in_stock_subscriptions', [
            'product_id' => $product->id, 'product_size_id' => null, 'email' => 'wholeproduct@example.com',
        ]);
    }

    public function test_duplicate_whole_product_signup_is_also_handled_gracefully(): void
    {
        // Regression guard for the NULL-uniqueness gap: MySQL/SQLite unique
        // indexes don't treat two NULLs as equal, so this case is only
        // caught by the controller's own exists-check, not the DB
        // constraint — prove it actually works, not just that the DB
        // constraint exists.
        $product = $this->makeProduct();

        $this->postJson(route('back-in-stock.store', $product), ['email' => 'dup@example.com'])->assertOk();
        $second = $this->postJson(route('back-in-stock.store', $product), ['email' => 'dup@example.com']);

        $second->assertOk();
        $this->assertSame(1, BackInStockSubscription::where('product_id', $product->id)
            ->whereNull('product_size_id')
            ->where('email', 'dup@example.com')
            ->count());
    }

    public function test_signup_rejects_an_invalid_email(): void
    {
        $product = $this->makeProduct();

        $response = $this->postJson(route('back-in-stock.store', $product), ['email' => 'not-an-email']);

        $response->assertStatus(422);
    }

    // ---------------------------------------------------------------
    // Notification trigger
    // ---------------------------------------------------------------

    public function test_service_notifies_size_specific_subscribers_when_stock_crosses_zero_to_positive(): void
    {
        Mail::fake();

        $product = $this->makeProduct();
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 0]);
        $subscription = BackInStockSubscription::create([
            'product_id' => $product->id, 'product_size_id' => $size->id, 'email' => 'watch@example.com',
        ]);

        app(BackInStockService::class)->checkAndNotify($product, $size, before: 0, after: 3);

        Mail::assertQueued(ProductBackInStockMail::class, fn ($mail) => $mail->hasTo('watch@example.com')
            && $mail->product->is($product)
            && $mail->size?->is($size));

        $this->assertNotNull($subscription->fresh()->notified_at);
    }

    public function test_service_does_not_notify_when_stock_increases_but_stays_above_zero(): void
    {
        Mail::fake();

        $product = $this->makeProduct();
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 3]);
        BackInStockSubscription::create([
            'product_id' => $product->id, 'product_size_id' => $size->id, 'email' => 'watch@example.com',
        ]);

        // 3 -> 5 is not a 0-to-positive crossing.
        app(BackInStockService::class)->checkAndNotify($product, $size, before: 3, after: 5);

        Mail::assertNothingQueued();
    }

    public function test_already_notified_subscription_is_not_notified_again(): void
    {
        Mail::fake();

        $product = $this->makeProduct();
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 0]);
        $subscription = BackInStockSubscription::create([
            'product_id' => $product->id, 'product_size_id' => $size->id, 'email' => 'watch@example.com',
            'notified_at' => now(),
        ]);

        app(BackInStockService::class)->checkAndNotify($product, $size, before: 0, after: 4);

        Mail::assertNothingQueued();
    }

    public function test_whole_product_subscribers_are_notified_via_the_products_only_size_row(): void
    {
        Mail::fake();

        // Exactly one size row = "no meaningful size choice" in the UI, so
        // its subscription is whole-product-level (product_size_id null).
        $product = $this->makeProduct();
        $size = $product->sizes()->create(['size' => 'Free Size', 'stock' => 0]);
        $subscription = BackInStockSubscription::create([
            'product_id' => $product->id, 'product_size_id' => null, 'email' => 'wholeproduct@example.com',
        ]);

        app(BackInStockService::class)->checkAndNotify($product, $size, before: 0, after: 2);

        Mail::assertQueued(ProductBackInStockMail::class, fn ($mail) => $mail->hasTo('wholeproduct@example.com'));
        $this->assertNotNull($subscription->fresh()->notified_at);
    }

    public function test_multi_size_product_does_not_notify_whole_product_subscribers(): void
    {
        Mail::fake();

        $product = $this->makeProduct();
        $sizeM = $product->sizes()->create(['size' => 'M', 'stock' => 0]);
        $product->sizes()->create(['size' => 'L', 'stock' => 5]);

        // A whole-product subscription shouldn't exist for a multi-size
        // product via the UI, but prove the service doesn't notify it even
        // if one somehow does (e.g. leftover from before sizes were added).
        BackInStockSubscription::create([
            'product_id' => $product->id, 'product_size_id' => null, 'email' => 'stale@example.com',
        ]);

        app(BackInStockService::class)->checkAndNotify($product, $sizeM, before: 0, after: 3);

        Mail::assertNotQueued(ProductBackInStockMail::class, fn ($mail) => $mail->hasTo('stale@example.com'));
    }

    public function test_notification_fires_when_stock_is_restored_via_admin_product_edit(): void
    {
        Mail::fake();

        $admin = $this->makeAdmin();
        $product = $this->makeProduct('Edited Product');
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 0]);
        BackInStockSubscription::create([
            'product_id' => $product->id, 'product_size_id' => $size->id, 'email' => 'admin-edit@example.com',
        ]);

        $response = $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $this->defaultCategory()->id,
            'name_ar' => 'Edited Product', 'name_en' => 'Edited Product',
            'price' => 1000, 'status' => 'published',
            'sizes' => ['M' => 6],
        ]);

        $response->assertRedirect();
        Mail::assertQueued(ProductBackInStockMail::class, fn ($mail) => $mail->hasTo('admin-edit@example.com'));
    }

    protected function makeOrderWithStock(int $initialStock, int $orderedQty): array
    {
        $product = $this->makeProduct('Order Restock Product');
        $size = $product->sizes()->create(['size' => 'M', 'stock' => $initialStock]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-'.uniqid(),
            'customer_name' => 'Test Customer', 'customer_email' => 'test@example.com', 'customer_phone' => '01000000000',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => '123 Test St',
            'subtotal' => 1000 * $orderedQty, 'shipping_fee' => 0, 'total' => 1000 * $orderedQty,
            'status' => 'pending', 'payment_method' => 'cod', 'stock_deducted_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name_en,
            'size' => 'M', 'price' => 1000, 'quantity' => $orderedQty,
        ]);

        return [$order, $product, $size];
    }

    public function test_notification_fires_when_stock_is_restored_via_order_cancellation(): void
    {
        Mail::fake();

        $admin = $this->makeAdmin();
        [$order, $product, $size] = $this->makeOrderWithStock(initialStock: 0, orderedQty: 2);
        BackInStockSubscription::create([
            'product_id' => $product->id, 'product_size_id' => $size->id, 'email' => 'cancel-watch@example.com',
        ]);

        $response = $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'cancelled']);

        $response->assertRedirect();
        $this->assertSame(2, $size->fresh()->stock);
        Mail::assertQueued(ProductBackInStockMail::class, fn ($mail) => $mail->hasTo('cancel-watch@example.com'));
    }

    public function test_order_cancellation_does_not_notify_when_stock_was_already_above_zero(): void
    {
        Mail::fake();

        $admin = $this->makeAdmin();
        [$order, $product, $size] = $this->makeOrderWithStock(initialStock: 3, orderedQty: 2);
        BackInStockSubscription::create([
            'product_id' => $product->id, 'product_size_id' => $size->id, 'email' => 'no-notify@example.com',
        ]);

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'cancelled']);

        Mail::assertNothingQueued();
    }

    // ---------------------------------------------------------------
    // Unsubscribe
    // ---------------------------------------------------------------

    public function test_unsubscribe_with_a_valid_signature_deletes_the_subscription(): void
    {
        $product = $this->makeProduct();
        $subscription = BackInStockSubscription::create([
            'product_id' => $product->id, 'product_size_id' => null, 'email' => 'unsub@example.com',
        ]);

        $url = URL::signedRoute('back-in-stock.unsubscribe', ['subscription' => $subscription->id]);

        $response = $this->get($url);

        $response->assertRedirect();
        $this->assertDatabaseMissing('back_in_stock_subscriptions', ['id' => $subscription->id]);
    }

    public function test_unsubscribe_without_a_valid_signature_is_rejected(): void
    {
        $product = $this->makeProduct();
        $subscription = BackInStockSubscription::create([
            'product_id' => $product->id, 'product_size_id' => null, 'email' => 'unsub2@example.com',
        ]);

        $response = $this->get(route('back-in-stock.unsubscribe', ['subscription' => $subscription->id]));

        $response->assertForbidden();
        $this->assertDatabaseHas('back_in_stock_subscriptions', ['id' => $subscription->id]);
    }

    // ---------------------------------------------------------------
    // Cleanup (back-in-stock:prune)
    // ---------------------------------------------------------------

    public function test_prune_removes_old_subscriptions_for_an_archived_product(): void
    {
        $product = $this->makeProduct();
        $product->update(['status' => Product::STATUS_ARCHIVED]);
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 0]);
        $subscription = BackInStockSubscription::create([
            'product_id' => $product->id, 'product_size_id' => $size->id, 'email' => 'stale@example.com',
        ]);
        // created_at isn't mass-assignable (not in $fillable) — force it
        // directly so the row is actually old enough to test the threshold.
        $subscription->forceFill(['created_at' => now()->subMonths(7)])->save();

        $this->artisan('back-in-stock:prune')->assertSuccessful();

        $this->assertDatabaseMissing('back_in_stock_subscriptions', ['id' => $subscription->id]);
    }

    public function test_prune_removes_old_still_never_notified_subscriptions_for_a_product_still_at_zero_stock(): void
    {
        $product = $this->makeProduct();
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 0]);
        $subscription = BackInStockSubscription::create([
            'product_id' => $product->id, 'product_size_id' => $size->id, 'email' => 'stale2@example.com',
        ]);
        $subscription->forceFill(['created_at' => now()->subMonths(7)])->save();

        $this->artisan('back-in-stock:prune')->assertSuccessful();

        $this->assertDatabaseMissing('back_in_stock_subscriptions', ['id' => $subscription->id]);
    }

    public function test_prune_keeps_old_subscriptions_for_an_active_product_currently_in_stock(): void
    {
        // Old, but the product isn't archived and has real stock — an old
        // date alone must not be treated as "dead."
        $product = $this->makeProduct();
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 5]);
        $subscription = BackInStockSubscription::create([
            'product_id' => $product->id, 'product_size_id' => $size->id, 'email' => 'still-relevant@example.com',
        ]);
        $subscription->forceFill(['created_at' => now()->subMonths(7)])->save();

        $this->artisan('back-in-stock:prune')->assertSuccessful();

        $this->assertDatabaseHas('back_in_stock_subscriptions', ['id' => $subscription->id]);
    }

    public function test_prune_keeps_recent_subscriptions_even_for_an_archived_product(): void
    {
        $product = $this->makeProduct();
        $product->update(['status' => Product::STATUS_ARCHIVED]);
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 0]);
        $subscription = BackInStockSubscription::create([
            'product_id' => $product->id, 'product_size_id' => $size->id, 'email' => 'recent@example.com',
        ]);
        $subscription->forceFill(['created_at' => now()->subDays(10)])->save();

        $this->artisan('back-in-stock:prune')->assertSuccessful();

        $this->assertDatabaseHas('back_in_stock_subscriptions', ['id' => $subscription->id]);
    }

    public function test_prune_keeps_old_already_notified_subscriptions_for_a_still_active_product(): void
    {
        // Fulfilled once and the product is still a normal active listing —
        // nothing dead about this row, leave it alone.
        $product = $this->makeProduct();
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 0]);
        $subscription = BackInStockSubscription::create([
            'product_id' => $product->id, 'product_size_id' => $size->id, 'email' => 'fulfilled@example.com',
            'notified_at' => now()->subMonths(6),
        ]);
        $subscription->forceFill(['created_at' => now()->subMonths(7)])->save();

        $this->artisan('back-in-stock:prune')->assertSuccessful();

        $this->assertDatabaseHas('back_in_stock_subscriptions', ['id' => $subscription->id]);
    }

    public function test_prune_respects_the_months_option(): void
    {
        $product = $this->makeProduct();
        $product->update(['status' => Product::STATUS_ARCHIVED]);
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 0]);
        $subscription = BackInStockSubscription::create([
            'product_id' => $product->id, 'product_size_id' => $size->id, 'email' => 'two-months@example.com',
        ]);
        $subscription->forceFill(['created_at' => now()->subMonths(3)])->save();

        $this->artisan('back-in-stock:prune', ['--months' => 1])->assertSuccessful();

        $this->assertDatabaseMissing('back_in_stock_subscriptions', ['id' => $subscription->id]);
    }
}
