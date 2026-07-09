<?php

namespace Tests\Feature\Admin;

use App\Models\Cart;
use App\Models\CartReminder;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CartManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    protected function customer(array $overrides = []): User
    {
        Role::findOrCreate('customer', 'web');
        $customer = User::factory()->create($overrides);
        $customer->assignRole('customer');

        return $customer;
    }

    protected function cartWithItem(User $customer, array $overrides = []): Cart
    {
        $category = Category::create(['name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'category-'.uniqid()]);
        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => 'منتج', 'name_en' => 'Product', 'slug' => 'product-'.uniqid(),
            'price' => 500, 'is_active' => true, 'is_featured' => false,
        ]);

        $cart = Cart::create(array_merge([
            'user_id' => $customer->id,
            'status' => 'active',
            'subtotal' => 500,
            'total' => 500,
            'items_count' => 1,
            'last_activity_at' => now(),
        ], $overrides));

        $cart->items()->create([
            'product_id' => $product->id,
            'product_name' => $product->name_en,
            'quantity' => 1,
            'price' => 500,
            'total' => 500,
        ]);

        return $cart;
    }

    public function test_non_admin_cannot_access_cart_routes(): void
    {
        $customer = $this->customer();
        $cart = $this->cartWithItem($customer);

        $this->actingAs($customer)->get(route('admin.carts.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.carts.show', $cart))->assertForbidden();
    }

    public function test_index_status_filter_narrows_results(): void
    {
        $admin = $this->admin();
        $activeCustomer = $this->customer(['name' => 'Active Shopper', 'email' => 'active@example.com']);
        $abandonedCustomer = $this->customer(['name' => 'Abandoned Shopper', 'email' => 'abandoned@example.com']);

        $this->cartWithItem($activeCustomer, ['status' => 'active']);
        $this->cartWithItem($abandonedCustomer, ['status' => 'abandoned']);

        $response = $this->actingAs($admin)->get(route('admin.carts.index', ['status' => 'abandoned']));

        $response->assertOk();
        $response->assertSee('Abandoned Shopper');
        $response->assertDontSee('Active Shopper');
    }

    public function test_index_search_narrows_results(): void
    {
        $admin = $this->admin();
        $match = $this->customer(['name' => 'Search Match', 'email' => 'match@example.com']);
        $other = $this->customer(['name' => 'No Match Here', 'email' => 'nomatch@example.com']);

        $this->cartWithItem($match);
        $this->cartWithItem($other);

        $response = $this->actingAs($admin)->get(route('admin.carts.index', ['search' => 'Search Match']));

        $response->assertOk();
        $response->assertSee('Search Match');
        $response->assertDontSee('No Match Here');
    }

    public function test_manual_reminder_creates_cart_reminder_and_increments_counter(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $cart = $this->cartWithItem($customer, ['status' => 'active']);

        $this->actingAs($admin)
            ->post(route('admin.carts.sendReminder', $cart))
            ->assertRedirect();

        $cart->refresh();
        $this->assertSame('abandoned', $cart->status);
        $this->assertSame(1, $cart->reminder_count);
        $this->assertSame(1, CartReminder::where('cart_id', $cart->id)->count());
    }

    public function test_cannot_remind_a_converted_cart(): void
    {
        $admin = $this->admin();
        $customer = $this->customer();
        $cart = $this->cartWithItem($customer, ['status' => 'converted', 'converted_at' => now()]);

        $this->actingAs($admin)
            ->post(route('admin.carts.sendReminder', $cart))
            ->assertRedirect();

        $cart->refresh();
        $this->assertSame('converted', $cart->status);
        $this->assertSame(0, $cart->reminder_count);
        $this->assertSame(0, CartReminder::where('cart_id', $cart->id)->count());
    }

    public function test_bulk_reminder_dispatches_for_each_eligible_cart_and_skips_converted(): void
    {
        $admin = $this->admin();

        $customerOne = $this->customer(['email' => 'one@example.com']);
        $customerTwo = $this->customer(['email' => 'two@example.com']);
        $customerThree = $this->customer(['email' => 'three@example.com']);

        $cartOne = $this->cartWithItem($customerOne, ['status' => 'active']);
        $cartTwo = $this->cartWithItem($customerTwo, ['status' => 'abandoned', 'last_activity_at' => now()->subHours(3)]);
        $cartConverted = $this->cartWithItem($customerThree, ['status' => 'converted', 'converted_at' => now()]);

        $this->actingAs($admin)
            ->post(route('admin.carts.bulkReminder'), [
                'cart_ids' => [$cartOne->id, $cartTwo->id, $cartConverted->id],
            ])
            ->assertRedirect();

        $this->assertSame(1, $cartOne->fresh()->reminder_count);
        $this->assertSame(1, $cartTwo->fresh()->reminder_count);
        $this->assertSame(0, $cartConverted->fresh()->reminder_count);

        $this->assertSame(2, CartReminder::whereIn('cart_id', [$cartOne->id, $cartTwo->id])->count());
        $this->assertSame(0, CartReminder::where('cart_id', $cartConverted->id)->count());
    }
}
