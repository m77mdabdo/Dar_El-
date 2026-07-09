<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class MigrateProductSizesToVariantsTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProductWithSizes(): Product
    {
        $category = Category::create(['name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);
        $product = Product::create(['category_id' => $category->id, 'name_ar' => 'منتج', 'name_en' => 'Product', 'slug' => 'product-'.uniqid(), 'price' => 300, 'is_active' => true, 'is_featured' => false]);

        $product->sizes()->create(['size' => 'S', 'stock' => 3]);
        $product->sizes()->create(['size' => 'M', 'stock' => 6]);
        $product->sizes()->create(['size' => 'L', 'stock' => 10]);

        return $product;
    }

    public function test_command_creates_a_size_option_with_matching_variants(): void
    {
        $product = $this->makeProductWithSizes();

        Artisan::call('products:migrate-sizes-to-variants');

        $product->refresh();

        $this->assertSame(1, $product->options()->count());
        $option = $product->options()->first();
        $this->assertSame('Size', $option->name_en);
        $this->assertSame(3, $option->values()->count());
        $this->assertSame(3, $product->variants()->count());

        $stocks = $product->variants()->pluck('stock')->sort()->values()->all();
        $this->assertSame([3, 6, 10], $stocks);
    }

    public function test_command_is_idempotent(): void
    {
        $product = $this->makeProductWithSizes();

        Artisan::call('products:migrate-sizes-to-variants');
        Artisan::call('products:migrate-sizes-to-variants');

        $product->refresh();

        $this->assertSame(1, $product->options()->count());
        $this->assertSame(3, $product->variants()->count());
    }

    public function test_command_does_not_touch_product_sizes_table(): void
    {
        $product = $this->makeProductWithSizes();

        Artisan::call('products:migrate-sizes-to-variants');

        $this->assertSame(3, $product->sizes()->count());
        $this->assertDatabaseHas('product_sizes', ['product_id' => $product->id, 'size' => 'M', 'stock' => 6]);
    }

    public function test_command_skips_products_with_no_sizes(): void
    {
        $category = Category::create(['name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-empty', 'is_active' => true, 'sort_order' => 1]);
        $product = Product::create(['category_id' => $category->id, 'name_ar' => 'م', 'name_en' => 'No Sizes', 'slug' => 'no-sizes', 'price' => 100, 'is_active' => true, 'is_featured' => false]);

        Artisan::call('products:migrate-sizes-to-variants');

        $this->assertSame(0, $product->options()->count());
    }
}
