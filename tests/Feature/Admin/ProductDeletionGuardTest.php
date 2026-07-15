<?php

namespace Tests\Feature\Admin;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductDeletionGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    protected function product(): Product
    {
        $category = Category::create([
            'name_ar' => 'عبايات', 'name_en' => 'Abayas', 'slug' => 'abayas-'.uniqid(),
            'is_active' => true, 'sort_order' => 1,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name_ar' => 'عباية', 'name_en' => 'Abaya', 'slug' => 'abaya-'.uniqid(),
            'price' => 1000, 'is_active' => true, 'is_featured' => false,
        ]);
    }

    protected function orderWithItem(Product $product, string $status): Order
    {
        $order = Order::create([
            'order_number' => 'ORD-'.uniqid(),
            'customer_name' => 'Test Customer', 'customer_email' => 'customer@example.com', 'customer_phone' => '01000000000',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => '123 Test St',
            'subtotal' => 1000, 'shipping_fee' => 0, 'discount_amount' => 0, 'total' => 1000,
            'status' => $status, 'payment_method' => Order::PAYMENT_METHOD_COD,
        ]);

        $order->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name_en,
            'size' => 'M', 'price' => 1000, 'quantity' => 1,
        ]);

        return $order;
    }

    protected function activeCartWithItem(Product $product): Cart
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('customer', 'web'));

        $cart = Cart::create([
            'user_id' => $user->id, 'status' => 'active', 'subtotal' => 1000, 'total' => 1000, 'items_count' => 1,
            'last_activity_at' => now(),
        ]);

        $cart->items()->create([
            'product_id' => $product->id, 'product_name' => $product->name_en,
            'quantity' => 1, 'price' => 1000, 'total' => 1000,
        ]);

        return $cart;
    }

    public function test_deleting_a_product_on_a_pending_order_is_blocked(): void
    {
        $admin = $this->admin();
        $product = $this->product();
        $this->orderWithItem($product, 'pending');

        $response = $this->actingAs($admin)->delete(route('admin.products.destroy', $product));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_deleting_a_product_on_a_processing_or_shipped_order_is_blocked(): void
    {
        $admin = $this->admin();

        foreach (['processing', 'shipped'] as $status) {
            $product = $this->product();
            $this->orderWithItem($product, $status);

            $response = $this->actingAs($admin)->delete(route('admin.products.destroy', $product));

            $response->assertSessionHas('error');
            $this->assertDatabaseHas('products', ['id' => $product->id]);
        }
    }

    /**
     * Explicit confirmation that ONLY non-terminal statuses block deletion
     * — a delivered or cancelled order is historically closed (its own
     * snapshot columns already preserve the record) and must not prevent
     * an otherwise-unblocked product from being deleted.
     */
    public function test_deleting_a_product_with_only_delivered_or_cancelled_orders_succeeds(): void
    {
        $admin = $this->admin();

        foreach (['delivered', 'cancelled'] as $status) {
            $product = $this->product();
            $this->orderWithItem($product, $status);

            $response = $this->actingAs($admin)->delete(route('admin.products.destroy', $product));

            $response->assertRedirect(route('admin.products.index'));
            $response->assertSessionHas('status');
            $this->assertDatabaseMissing('products', ['id' => $product->id]);
        }
    }

    public function test_deleting_a_product_in_an_active_cart_is_blocked(): void
    {
        $admin = $this->admin();
        $product = $this->product();
        $this->activeCartWithItem($product);

        $response = $this->actingAs($admin)->delete(route('admin.products.destroy', $product));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_deleting_a_product_with_no_orders_or_carts_succeeds(): void
    {
        $admin = $this->admin();
        $product = $this->product();

        $response = $this->actingAs($admin)->delete(route('admin.products.destroy', $product));

        $response->assertRedirect(route('admin.products.index'));
        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
