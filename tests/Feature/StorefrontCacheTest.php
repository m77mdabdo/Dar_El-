<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorefrontCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCategory(string $nameEn = 'Test Category'): Category
    {
        return Category::create([
            'name_ar' => $nameEn, 'name_en' => $nameEn, 'slug' => Str::slug($nameEn).'-'.uniqid(),
            'is_active' => true, 'sort_order' => 1,
        ]);
    }

    protected function makeFeaturedProduct(string $nameEn = 'Test Product', ?Category $category = null): Product
    {
        $category ??= $this->makeCategory();

        return Product::create([
            'category_id' => $category->id,
            'name_ar' => $nameEn, 'name_en' => $nameEn, 'slug' => Str::slug($nameEn).'-'.uniqid(),
            'price' => 1000, 'is_active' => true, 'is_featured' => true,
        ]);
    }

    protected function makeProductInCategory(Category $category, string $nameEn): Product
    {
        return Product::create([
            'category_id' => $category->id,
            'name_ar' => $nameEn, 'name_en' => $nameEn, 'slug' => Str::slug($nameEn).'-'.uniqid(),
            'price' => 1000, 'is_active' => true, 'is_featured' => false,
        ]);
    }

    /**
     * Counts queries against a given table name across a request, filtering
     * DB::getQueryLog() rather than asserting an exact total count (which
     * would be brittle against unrelated query changes elsewhere on the
     * page) — this isolates specifically whether the cached tables were
     * ever actually hit.
     */
    protected function queryCountForTable(string $table): int
    {
        return collect(DB::getQueryLog())
            ->filter(fn ($entry) => str_contains($entry['query'], "\"{$table}\"") || str_contains($entry['query'], "`{$table}`"))
            ->count();
    }

    public function test_home_page_products_and_categories_are_cached_and_not_requeried_on_a_second_request(): void
    {
        $this->makeFeaturedProduct('Cached Featured Item');

        DB::enableQueryLog();
        $this->get(route('home'))->assertOk();
        $firstProductQueries = $this->queryCountForTable('products');
        $firstCategoryQueries = $this->queryCountForTable('categories');
        $this->assertGreaterThan(0, $firstProductQueries, 'Expected the first request to genuinely query products.');
        $this->assertGreaterThan(0, $firstCategoryQueries, 'Expected the first request to genuinely query categories.');

        DB::flushQueryLog();
        $this->get(route('home'))->assertOk();
        $secondProductQueries = $this->queryCountForTable('products');
        $secondCategoryQueries = $this->queryCountForTable('categories');

        $this->assertSame(0, $secondProductQueries, 'Second request within the cache TTL re-queried products instead of serving from cache.');
        $this->assertSame(0, $secondCategoryQueries, 'Second request within the cache TTL re-queried categories instead of serving from cache.');
    }

    public function test_creating_a_featured_product_busts_the_home_page_cache(): void
    {
        // Prime the cache with the page as it exists before the new product.
        $this->get(route('home'))->assertOk()->assertDontSee('Brand New Featured Item');
        $this->assertTrue(Cache::has('storefront.home.data'));

        $this->makeFeaturedProduct('Brand New Featured Item');

        $this->assertFalse(Cache::has('storefront.home.data'), 'Cache::forget() did not run on Product creation.');
        $this->get(route('home'))->assertOk()->assertSee('Brand New Featured Item');
    }

    public function test_updating_a_featured_product_busts_the_home_page_cache(): void
    {
        $product = $this->makeFeaturedProduct('Original Product Name');
        $this->get(route('home'))->assertOk()->assertSee('Original Product Name');

        $product->update(['name_en' => 'Updated Product Name', 'name_ar' => 'Updated Product Name']);

        $this->assertFalse(Cache::has('storefront.home.data'));
        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee('Updated Product Name');
        $response->assertDontSee('Original Product Name');
    }

    public function test_deleting_a_featured_product_busts_the_home_page_cache(): void
    {
        $product = $this->makeFeaturedProduct('Soon To Be Deleted');
        $this->get(route('home'))->assertOk()->assertSee('Soon To Be Deleted');

        $product->delete();

        $this->assertFalse(Cache::has('storefront.home.data'));
        $this->get(route('home'))->assertOk()->assertDontSee('Soon To Be Deleted');
    }

    public function test_creating_a_category_busts_the_shared_cache_on_both_home_and_shop_pages(): void
    {
        $this->get(route('home'))->assertOk()->assertDontSee('Brand New Category');
        $this->assertTrue(Cache::has('storefront.categories'));

        $this->makeCategory('Brand New Category');

        $this->assertFalse(Cache::has('storefront.categories'), 'Cache::forget() did not run on Category creation.');
        $this->get(route('home'))->assertOk()->assertSee('Brand New Category');

        // The shop page shares the exact same cache key — confirm it also
        // reflects the new category rather than needing its own separate bust.
        $this->get(route('shop.index'))->assertOk()->assertSee('Brand New Category');
    }

    public function test_deleting_a_category_busts_the_shared_cache(): void
    {
        $category = $this->makeCategory('Soon Gone Category');
        $this->get(route('home'))->assertOk()->assertSee('Soon Gone Category');

        $category->delete();

        $this->assertFalse(Cache::has('storefront.categories'));
        $this->get(route('home'))->assertOk()->assertDontSee('Soon Gone Category');
    }

    /**
     * The one deliberately-uncached piece: ShopController's filtered/sorted/
     * paginated product listing. Proves two things together — different
     * filters produce correct, non-stale results (not one filter's cached
     * output bleeding into another), AND the products table is genuinely
     * queried on every single request, never served from a cache hit.
     */
    public function test_shop_filtered_product_listing_always_queries_fresh_and_never_serves_stale_filtered_results(): void
    {
        $categoryA = $this->makeCategory('Category A');
        $categoryB = $this->makeCategory('Category B');
        $this->makeProductInCategory($categoryA, 'Product In A');
        $this->makeProductInCategory($categoryB, 'Product In B');

        $responseA = $this->get(route('shop.index', ['category' => $categoryA->slug]));
        $responseA->assertOk();
        $responseA->assertSee('Product In A');
        $responseA->assertDontSee('Product In B');

        $responseB = $this->get(route('shop.index', ['category' => $categoryB->slug]));
        $responseB->assertOk();
        $responseB->assertSee('Product In B');
        $responseB->assertDontSee('Product In A');

        // Re-request the first filter again — if the product query were
        // cached under any shared key, this is exactly where a stale/wrong
        // result would surface.
        $responseA2 = $this->get(route('shop.index', ['category' => $categoryA->slug]));
        $responseA2->assertSee('Product In A');
        $responseA2->assertDontSee('Product In B');

        DB::enableQueryLog();
        $this->get(route('shop.index', ['category' => $categoryA->slug]));
        $this->assertGreaterThan(0, $this->queryCountForTable('products'), 'Expected the filtered product listing to query fresh on every request — it appears to be cached.');
    }
}
