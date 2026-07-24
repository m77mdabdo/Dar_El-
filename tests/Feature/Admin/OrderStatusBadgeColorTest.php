<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Admin usability audit finding #2: 'processing' and 'shipped' order-status
 * badges rendered with the identical color (both a generic "info" blue) on
 * the admin orders list, and — worse — the admin customer-detail order
 * tables ignored status entirely and showed every single status in that
 * same blue. Order::statusBadgeColor() is now the one shared mapping every
 * one of these views calls (see account/orders/index.blade.php's own
 * OrderHistoryCardTest for the customer-facing side, which established the
 * canonical colors this fix reuses).
 */
class OrderStatusBadgeColorTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    protected function makeOrder(string $status, ?User $user = null): Order
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-'.uniqid(),
            'is_active' => true, 'sort_order' => 1,
        ]);
        $product = Product::create([
            'category_id' => $category->id, 'name_ar' => 'منتج', 'name_en' => 'Product',
            'slug' => 'product-'.uniqid(), 'price' => 500, 'is_active' => true, 'is_featured' => false,
        ]);

        $order = Order::create([
            'user_id' => $user?->id,
            'order_number' => 'ORD-'.uniqid(),
            'customer_name' => 'Test Customer', 'customer_email' => 'customer@example.com', 'customer_phone' => '01000000000',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => 'Street 1',
            'locale' => 'en', 'subtotal' => 500, 'shipping_fee' => 50, 'discount_amount' => 0, 'total' => 550,
            'status' => $status, 'payment_method' => Order::PAYMENT_METHOD_COD,
        ]);
        $order->items()->create(['product_id' => $product->id, 'product_name' => 'Product', 'size' => 'M', 'price' => 500, 'quantity' => 1]);

        return $order;
    }

    // ---------------------------------------------------------------
    // The mapping itself
    // ---------------------------------------------------------------

    public function test_every_known_status_maps_to_a_genuinely_distinct_color(): void
    {
        $statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        $colors = collect($statuses)->map(fn ($status) => Order::statusBadgeColor($status));

        // Both the background AND the foreground would have to collide for
        // two statuses to actually look the same — comparing the pair as
        // one string is what catches that.
        $signatures = $colors->map(fn ($c) => $c['bg'].'|'.$c['fg']);

        $this->assertSame(
            $signatures->count(),
            $signatures->unique()->count(),
            'Two different order statuses share the exact same badge color.'
        );
    }

    public function test_processing_and_shipped_specifically_are_no_longer_the_same_color(): void
    {
        // The literal audit finding: both used to fall into the same
        // "default => info blue" branch.
        $this->assertNotSame(
            Order::statusBadgeColor('processing'),
            Order::statusBadgeColor('shipped')
        );
    }

    // ---------------------------------------------------------------
    // Admin orders list
    // ---------------------------------------------------------------

    public static function statusColorProvider(): array
    {
        return [
            'pending' => ['pending', 'rgba(232,195,154,.35)', '#8a5a2a'],
            'processing' => ['processing', 'rgba(59,130,246,.12)', '#2563eb'],
            'shipped' => ['shipped', 'rgba(147,51,234,.12)', '#7e22ce'],
            'delivered' => ['delivered', 'rgba(47,122,77,.12)', '#2f7a4d'],
            'cancelled' => ['cancelled', 'rgba(156,80,100,.12)', '#9C5064'],
        ];
    }

    #[DataProvider('statusColorProvider')]
    public function test_admin_orders_list_shows_the_correct_badge_color_for_each_status(string $status, string $bg, string $fg): void
    {
        $admin = $this->makeAdmin();
        $order = $this->makeOrder($status);

        $response = $this->actingAs($admin)->get(route('admin.orders.index'));

        $response->assertOk();
        $response->assertSee($order->order_number);
        $response->assertSee('background:'.$bg.'; color:'.$fg.';', false);
    }

    // ---------------------------------------------------------------
    // Admin customer detail — the worse variant of the bug (every status
    // rendered identically, not just processing/shipped colliding)
    // ---------------------------------------------------------------

    public function test_admin_customer_orders_tab_gives_processing_and_shipped_different_colors(): void
    {
        $admin = $this->makeAdmin();
        $customer = User::factory()->create();
        $customer->assignRole(Role::findOrCreate('customer', 'web'));
        $processing = $this->makeOrder('processing', $customer);
        $shipped = $this->makeOrder('shipped', $customer);

        $response = $this->actingAs($admin)->get(route('admin.customers.orders', $customer));

        $response->assertOk();
        $response->assertSee('background:rgba(59,130,246,.12); color:#2563eb;', false);
        $response->assertSee('background:rgba(147,51,234,.12); color:#7e22ce;', false);
    }

    public function test_admin_customer_detail_page_gives_processing_and_shipped_different_colors(): void
    {
        $admin = $this->makeAdmin();
        $customer = User::factory()->create();
        $customer->assignRole(Role::findOrCreate('customer', 'web'));
        $this->makeOrder('processing', $customer);
        $this->makeOrder('shipped', $customer);

        $response = $this->actingAs($admin)->get(route('admin.customers.show', $customer));

        $response->assertOk();
        $response->assertSee('background:rgba(59,130,246,.12); color:#2563eb;', false);
        $response->assertSee('background:rgba(147,51,234,.12); color:#7e22ce;', false);
    }
}
