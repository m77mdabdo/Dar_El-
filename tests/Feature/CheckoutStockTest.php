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
            'shipping_method_id' => $shippingMethod->id,
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
}
