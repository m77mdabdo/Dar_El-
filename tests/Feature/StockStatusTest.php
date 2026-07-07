<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // The SetLocale middleware re-derives locale from session('locale')
        // on every request (defaulting to the app's Arabic locale), so force
        // English for deterministic string assertions.
        $this->withSession(['locale' => 'en']);
    }

    protected function makeProduct(): Product
    {
        $category = Category::create([
            'name_ar' => 'عبايات', 'name_en' => 'Abayas', 'slug' => 'abayas', 'is_active' => true, 'sort_order' => 1,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name_ar' => 'عباية', 'name_en' => 'Abaya', 'slug' => 'abaya-'.uniqid(),
            'price' => 1000, 'is_active' => true, 'is_featured' => false,
        ]);
    }

    public function test_stock_status_out_of_stock(): void
    {
        $product = $this->makeProduct();
        $product->sizes()->create(['size' => 'M', 'stock' => 0]);

        $this->assertSame('out_of_stock', $product->fresh(['sizes'])->stockStatus()['status']);
    }

    public function test_stock_status_low_stock_at_threshold(): void
    {
        $product = $this->makeProduct();
        $product->sizes()->create(['size' => 'M', 'stock' => 5]);

        $status = $product->fresh(['sizes'])->stockStatus();
        $this->assertSame('low_stock', $status['status']);
        $this->assertSame(5, $status['stock']);
    }

    public function test_stock_status_in_stock_above_threshold(): void
    {
        $product = $this->makeProduct();
        $product->sizes()->create(['size' => 'M', 'stock' => 6]);

        $this->assertSame('in_stock', $product->fresh(['sizes'])->stockStatus()['status']);
    }

    public function test_stock_status_is_calculated_per_size_not_just_total(): void
    {
        $product = $this->makeProduct();
        $product->sizes()->create(['size' => 'S', 'stock' => 20]);
        $product->sizes()->create(['size' => 'M', 'stock' => 0]);
        $product = $product->fresh(['sizes']);

        // Product-level status uses total stock across sizes...
        $this->assertSame('in_stock', $product->stockStatus()['status']);
        // ...but a specific size can still be out of stock.
        $this->assertSame(0, $product->stockForSize('M'));
        $this->assertSame('out_of_stock', $product->stockStatus($product->stockForSize('M'))['status']);
    }

    public function test_shop_page_shows_stock_badges(): void
    {
        $product = $this->makeProduct();
        $product->sizes()->create(['size' => 'M', 'stock' => 3]);

        $response = $this->get(route('shop.index'));

        $response->assertOk();
        $response->assertSee('Only 3 left');
    }
}
