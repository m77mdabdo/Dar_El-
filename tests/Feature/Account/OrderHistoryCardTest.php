<?php

namespace Tests\Feature\Account;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Covers the order-history card redesign (account/orders/index.blade.php) —
 * specifically the per-status badge color/label mapping, which previously
 * had no direct assertions (only an incidental order-number check
 * elsewhere).
 */
class OrderHistoryCardTest extends TestCase
{
    use RefreshDatabase;

    protected function makeOrder(User $user, string $status): Order
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-'.uniqid(),
            'is_active' => true, 'sort_order' => 1,
        ]);
        $product = Product::create([
            'category_id' => $category->id, 'name_ar' => 'Product', 'name_en' => 'Product',
            'slug' => 'product-'.uniqid(), 'price' => 500, 'is_active' => true, 'is_featured' => false,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'ORD-'.uniqid(),
            'customer_name' => 'Test Customer', 'customer_email' => $user->email, 'customer_phone' => '01000000000',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => 'Street 1',
            'locale' => 'en', 'subtotal' => 500, 'shipping_fee' => 50, 'discount_amount' => 0, 'total' => 550,
            'status' => $status, 'payment_method' => Order::PAYMENT_METHOD_COD,
        ]);
        $order->items()->create(['product_id' => $product->id, 'product_name' => 'Product', 'size' => 'M', 'price' => 500, 'quantity' => 1]);

        return $order;
    }

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
    public function test_order_card_shows_the_correct_badge_color_and_label_for_each_status(string $status, string $bg, string $fg): void
    {
        $user = User::factory()->create();
        $order = $this->makeOrder($user, $status);

        $response = $this->actingAs($user)->get(route('account.orders.index'));

        $response->assertOk();
        $response->assertSee($order->order_number);
        $response->assertSee(__('orders.status_'.$status));
        $response->assertSee('background:'.$bg.'; color:'.$fg.';', false);
    }

    public function test_unrecognized_status_falls_back_to_the_pending_color(): void
    {
        // Defensive fallback in the view (`$djStatusColors[$order->status] ?? $djStatusColors['pending']`)
        // for any status value that isn't one of the 5 known keys.
        $user = User::factory()->create();
        $order = $this->makeOrder($user, 'pending');
        $order->update(['status' => 'unknown_status']);

        $response = $this->actingAs($user)->get(route('account.orders.index'));

        $response->assertOk();
        $response->assertSee('background:rgba(232,195,154,.35); color:#8a5a2a;', false);
    }
}
