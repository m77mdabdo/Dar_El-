<?php

namespace Tests\Feature\Console;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishScheduledProductsTest extends TestCase
{
    use RefreshDatabase;

    protected function makeScheduledProduct(\DateTimeInterface|string $scheduledAt): Product
    {
        $category = Category::create(['name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-'.uniqid()]);

        return Product::create([
            'category_id' => $category->id, 'name_ar' => 'منتج', 'name_en' => 'Product',
            'slug' => 'product-'.uniqid(), 'price' => 300, 'is_featured' => false,
            'is_active' => false, 'status' => Product::STATUS_SCHEDULED, 'scheduled_publish_at' => $scheduledAt,
        ]);
    }

    public function test_publishes_products_whose_scheduled_time_has_passed(): void
    {
        $product = $this->makeScheduledProduct(now()->subMinute());

        $this->artisan('products:publish-scheduled')->assertSuccessful();

        $product->refresh();
        $this->assertSame(Product::STATUS_PUBLISHED, $product->status);
        $this->assertTrue($product->is_active);
        $this->assertNotNull($product->published_at);
    }

    public function test_leaves_future_scheduled_products_untouched(): void
    {
        $product = $this->makeScheduledProduct(now()->addDay());

        $this->artisan('products:publish-scheduled')->assertSuccessful();

        $product->refresh();
        $this->assertSame(Product::STATUS_SCHEDULED, $product->status);
        $this->assertFalse($product->is_active);
    }

    public function test_running_twice_is_a_no_op_the_second_time(): void
    {
        $product = $this->makeScheduledProduct(now()->subMinute());

        $this->artisan('products:publish-scheduled')->assertSuccessful();
        $publishedAt = $product->refresh()->published_at;

        $this->travel(5)->minutes();
        $this->artisan('products:publish-scheduled')->assertSuccessful();

        $product->refresh();
        $this->assertSame(Product::STATUS_PUBLISHED, $product->status);
        $this->assertTrue($product->published_at->equalTo($publishedAt));
    }
}
