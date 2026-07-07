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

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_with_seeded_data(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        Role::findOrCreate('customer', 'web');

        $category = Category::create(['name_ar' => 'ع', 'name_en' => 'Cat', 'slug' => 'cat-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);
        $product = Product::create(['category_id' => $category->id, 'name_ar' => 'م', 'name_en' => 'Prod', 'slug' => 'prod-'.uniqid(), 'price' => 500, 'is_active' => true, 'is_featured' => false]);
        $product->sizes()->create(['size' => 'M', 'stock' => 3]);

        $order = Order::create([
            'order_number' => 'ORD-TEST-'.uniqid(),
            'customer_name' => 'Test', 'customer_email' => 'test@example.com', 'customer_phone' => '010',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => 'x',
            'subtotal' => 500, 'shipping_fee' => 0, 'total' => 500, 'status' => 'delivered', 'payment_method' => 'cod',
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name_en, 'size' => 'M', 'price' => 500, 'quantity' => 1]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee($order->order_number);
        $response->assertSee('Prod');
        $response->assertSee('dj-admin-chart', false);
    }

    public function test_dashboard_renders_with_no_data(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));
        Role::findOrCreate('customer', 'web');

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
    }
}
