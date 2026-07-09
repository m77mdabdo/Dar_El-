<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CartTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProduct(int $stock = 10): Product
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

    public function test_authenticated_user_adding_to_cart_creates_cart_and_item_rows(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user)->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 2])->assertOk();

        $cart = Cart::where('user_id', $user->id)->first();

        $this->assertNotNull($cart);
        $this->assertSame('active', $cart->status);
        $this->assertSame(1, $cart->items()->count());
        $this->assertSame(2, $cart->items_count);
        $this->assertSame(2000, $cart->total);
    }

    public function test_updating_quantity_updates_existing_row_not_duplicate(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user)->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
        $key = $product->id.'-M';

        $this->actingAs($user)->patchJson(route('cart.update', $key), ['quantity' => 4])->assertOk();

        $this->assertSame(1, Cart::where('user_id', $user->id)->count());
        $cart = Cart::where('user_id', $user->id)->first();
        $this->assertSame(1, $cart->items()->count());
        $this->assertSame(4, $cart->items_count);
    }

    public function test_removing_last_item_deletes_cart_row(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user)->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
        $this->assertSame(1, Cart::where('user_id', $user->id)->count());

        $key = $product->id.'-M';
        $this->actingAs($user)->deleteJson(route('cart.remove', $key))->assertOk();

        $this->assertSame(0, Cart::where('user_id', $user->id)->count());
    }

    public function test_guest_cart_activity_creates_no_cart_row(): void
    {
        $product = $this->makeProduct();

        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        $this->assertSame(0, Cart::count());
    }

    public function test_checkout_marks_cart_converted_with_order_id(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $product = $this->makeProduct();
        $shippingMethod = ShippingMethod::create(['name_ar' => 'شحن', 'name_en' => 'Shipping', 'fee' => 50, 'estimated_days' => '2-3', 'is_active' => true]);

        $this->actingAs($user)->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        $response = $this->actingAs($user)->post(route('checkout.store'), [
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '01000000000',
            'governorate' => 'Cairo',
            'city' => 'Nasr City',
            'address' => '123 Test St',
            'shipping_method_id' => $shippingMethod->id,
        ]);

        $order = Order::first();
        $response->assertRedirect(route('checkout.success', $order));

        $cart = Cart::where('user_id', $user->id)->first();
        $this->assertNotNull($cart);
        $this->assertSame('converted', $cart->status);
        $this->assertSame($order->id, $cart->order_id);
        $this->assertNotNull($cart->converted_at);
    }
}
