<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CheckoutFlowTest extends TestCase
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

    protected function checkoutPayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '01000000000',
            'governorate' => 'Cairo',
            'city' => 'Nasr City',
            'address' => '123 Test St',
            'shipping_method_id' => 'standard',
            'payment_method' => Order::PAYMENT_METHOD_COD,
        ], $overrides);
    }

    public function test_checkout_page_never_shows_zero_shipping_methods(): void
    {
        $this->assertSame(0, ShippingMethod::count());

        $user = User::factory()->create();
        $product = $this->makeProduct();
        $this->actingAs($user)->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        $response = $this->actingAs($user)->get('/checkout');

        $response->assertOk();
        $this->assertGreaterThan(0, ShippingMethod::where('is_active', true)->count());
    }

    public function test_order_can_be_placed_against_the_self_healed_standard_fallback(): void
    {
        $this->assertSame(0, ShippingMethod::count());

        $user = User::factory()->create();
        $product = $this->makeProduct();
        $this->actingAs($user)->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        $response = $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($user));

        $order = Order::first();
        $response->assertRedirect(route('checkout.success', $order));
        $this->assertSame('standard', $order->shipping_method_code);
        $this->assertSame(Order::PAYMENT_METHOD_COD, $order->payment_method);
        $this->assertSame(Order::PAYMENT_STATUS_PENDING, $order->payment_status);
    }

    public function test_selecting_a_real_shipping_method_snapshots_its_details_on_the_order(): void
    {
        $express = ShippingMethod::create([
            'code' => 'express', 'name_ar' => 'سريع', 'name_en' => 'Express', 'fee' => 150,
            'estimated_days' => '1-2', 'delivery_time_min_days' => 1, 'delivery_time_max_days' => 2, 'is_active' => true,
        ]);

        $user = User::factory()->create();
        $product = $this->makeProduct();
        $this->actingAs($user)->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($user, [
            'shipping_method_id' => (string) $express->id,
        ]));

        $order = Order::first();
        $this->assertSame('express', $order->shipping_method_code);
        $this->assertSame(trans_field($express, 'name'), $order->shipping_method_name);
        $this->assertSame(1, $order->shipping_delivery_min_days);
        $this->assertSame(2, $order->shipping_delivery_max_days);
        $this->assertSame(150, (int) $order->shipping_fee);
        $this->assertSame(1150, (int) $order->total);
    }

    public function test_payment_method_must_be_a_recognized_value(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $this->actingAs($user)->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        $response = $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($user, [
            'payment_method' => 'bank_transfer',
        ]));

        $response->assertSessionHasErrors('payment_method');
        $this->assertSame(0, Order::count());
    }

    public function test_geolocation_is_optional_and_never_blocks_order_creation(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $this->actingAs($user)->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        $response = $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($user));

        $order = Order::first();
        $response->assertRedirect(route('checkout.success', $order));
        $this->assertNull($order->customer_latitude);
        $this->assertNull($order->customer_longitude);
    }

    public function test_submitted_coordinates_are_stored_on_the_order(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $this->actingAs($user)->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($user, [
            'customer_latitude' => '30.0444196',
            'customer_longitude' => '31.2357116',
        ]));

        $order = Order::first();
        $this->assertEqualsWithDelta(30.0444196, (float) $order->customer_latitude, 0.00001);
        $this->assertEqualsWithDelta(31.2357116, (float) $order->customer_longitude, 0.00001);
    }

    public function test_success_page_shows_payment_and_shipping_details(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $this->actingAs($user)->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();

        $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($user));
        $order = Order::first();

        $response = $this->actingAs($user)->get(route('checkout.success', $order));

        $response->assertOk();
        $response->assertSee($order->order_number);
        $response->assertSee(__('Cash on Delivery'));
        $response->assertSee(route('account.orders.show', $order), false);
    }

    public function test_order_appears_in_admin_panel_and_customer_order_history(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $product = $this->makeProduct();
        $this->actingAs($user)->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
        $this->actingAs($user)->post(route('checkout.store'), $this->checkoutPayload($user));
        $order = Order::first();

        $this->actingAs($admin)->get(route('admin.orders.show', $order))->assertOk()->assertSee($order->order_number);
        $this->actingAs($user)->get(route('account.orders.index'))->assertOk()->assertSee($order->order_number);
    }
}
