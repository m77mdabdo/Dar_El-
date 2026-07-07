<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OrderStockRestorationTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    protected function makeOrderWithStock(int $initialStock, int $orderedQty): array
    {
        $category = Category::create([
            'name_ar' => 'عبايات', 'name_en' => 'Abayas', 'slug' => 'abayas', 'is_active' => true, 'sort_order' => 1,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => 'عباية', 'name_en' => 'Abaya', 'slug' => 'abaya-'.uniqid(),
            'price' => 1000, 'is_active' => true, 'is_featured' => false,
        ]);
        $size = $product->sizes()->create(['size' => 'M', 'stock' => $initialStock]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-'.uniqid(),
            'customer_name' => 'Test Customer',
            'customer_email' => 'test@example.com',
            'customer_phone' => '01000000000',
            'governorate' => 'Cairo',
            'city' => 'Nasr City',
            'address' => '123 Test St',
            'subtotal' => 1000 * $orderedQty,
            'shipping_fee' => 0,
            'total' => 1000 * $orderedQty,
            'status' => 'pending',
            'payment_method' => 'cod',
            'stock_deducted_at' => now(),
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_name' => $product->name_en,
            'size' => 'M',
            'price' => 1000,
            'quantity' => $orderedQty,
        ]);

        return [$order, $product, $size];
    }

    public function test_cancelling_an_order_restores_stock(): void
    {
        $admin = $this->makeAdmin();
        [$order, $product, $size] = $this->makeOrderWithStock(initialStock: 3, orderedQty: 2);

        $response = $this->actingAs($admin)->patch(route('admin.orders.status', $order), [
            'status' => 'cancelled',
        ]);

        $response->assertRedirect();
        $this->assertSame(5, $size->fresh()->stock);
        $this->assertNotNull($order->fresh()->stock_restored_at);
    }

    public function test_cancelling_an_already_cancelled_order_does_not_double_restore_stock(): void
    {
        $admin = $this->makeAdmin();
        [$order, $product, $size] = $this->makeOrderWithStock(initialStock: 3, orderedQty: 2);

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'cancelled']);
        $this->assertSame(5, $size->fresh()->stock);

        // Re-submitting the same cancelled status must not restore stock again.
        $this->actingAs($admin)->patch(route('admin.orders.status', $order->fresh()), ['status' => 'cancelled']);

        $this->assertSame(5, $size->fresh()->stock);
    }

    public function test_marking_order_as_processing_does_not_touch_stock(): void
    {
        $admin = $this->makeAdmin();
        [$order, $product, $size] = $this->makeOrderWithStock(initialStock: 3, orderedQty: 2);

        $this->actingAs($admin)->patch(route('admin.orders.status', $order), ['status' => 'processing']);

        $this->assertSame(3, $size->fresh()->stock);
        $this->assertNull($order->fresh()->stock_restored_at);
    }
}
