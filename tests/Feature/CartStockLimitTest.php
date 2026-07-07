<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartStockLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The SetLocale middleware re-derives the locale from session('locale')
        // on every request (defaulting to the app's Arabic locale), so assert
        // on deterministic English copy by forcing it via the session.
        $this->withSession(['locale' => 'en']);
    }

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

    public function test_cannot_add_more_than_available_stock(): void
    {
        $product = $this->makeProduct(2);

        $response = $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 3]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['error' => 'You can only order 2 piece(s) of this size.']);
    }

    public function test_adding_up_to_the_stock_limit_across_two_requests_is_blocked_on_the_second(): void
    {
        $product = $this->makeProduct(2);

        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 2])->assertOk();

        $response = $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1]);

        $response->assertStatus(422);
    }

    public function test_updating_cart_quantity_beyond_stock_is_blocked(): void
    {
        $product = $this->makeProduct(3);

        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
        $key = $product->id.'-M';

        $response = $this->patchJson(route('cart.update', $key), ['quantity' => 10]);

        $response->assertStatus(422);
        $response->assertJsonFragment(['error' => 'You can only order 3 piece(s) of this size.']);
    }

    public function test_updating_cart_quantity_within_stock_succeeds(): void
    {
        $product = $this->makeProduct(5);

        $this->postJson(route('cart.add', $product), ['size' => 'M', 'quantity' => 1])->assertOk();
        $key = $product->id.'-M';

        $response = $this->patchJson(route('cart.update', $key), ['quantity' => 4]);

        $response->assertOk();
    }
}
