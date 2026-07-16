<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShopFilterTest extends TestCase
{
    use RefreshDatabase;

    protected ?Category $defaultCategory = null;

    protected function makeCategory(string $nameEn): Category
    {
        return Category::create([
            'name_ar' => $nameEn, 'name_en' => $nameEn, 'slug' => Str::slug($nameEn).'-'.uniqid(),
            'is_active' => true, 'sort_order' => 1,
        ]);
    }

    /**
     * One shared, neutrally-named category reused by makeProduct() whenever
     * a test doesn't care about category identity. Deliberately NOT derived
     * from the product's own name — the category-chip nav bar renders every
     * active category's name on every request, so a category named e.g.
     * "Category for Golden Sash" leaks that product's name into the page
     * outside the product grid and corrupts assertSee/assertDontSee/strpos
     * checks that are only meant to look at the grid.
     */
    protected function defaultCategory(): Category
    {
        return $this->defaultCategory ??= $this->makeCategory('General');
    }

    protected function makeProduct(string $nameEn, int $price, ?Category $category = null, ?string $size = null, int $stock = 5): Product
    {
        $category ??= $this->defaultCategory();

        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => $nameEn, 'name_en' => $nameEn, 'slug' => Str::slug($nameEn).'-'.uniqid(),
            'price' => $price, 'is_active' => true, 'is_featured' => false,
        ]);

        if ($size !== null) {
            $product->sizes()->create(['size' => $size, 'stock' => $stock]);
        }

        return $product;
    }

    public function test_search_matches_partial_product_name_case_insensitively(): void
    {
        $this->makeProduct('Emerald Embroidered Belt', 500);
        $this->makeProduct('Golden Sash', 500);

        $response = $this->get(route('shop.index', ['q' => 'emb']));

        $response->assertOk();
        $response->assertSee('Emerald Embroidered Belt');
        $response->assertDontSee('Golden Sash');
    }

    public function test_search_matches_arabic_name_too(): void
    {
        $category = $this->makeCategory('Cat For Arabic');
        Product::create([
            'category_id' => $category->id,
            'name_ar' => 'عباية ذهبية', 'name_en' => 'Golden Abaya', 'slug' => 'golden-abaya-'.uniqid(),
            'price' => 500, 'is_active' => true, 'is_featured' => false,
        ]);
        Product::create([
            'category_id' => $category->id,
            'name_ar' => 'فستان أزرق', 'name_en' => 'Blue Dress', 'slug' => 'blue-dress-'.uniqid(),
            'price' => 500, 'is_active' => true, 'is_featured' => false,
        ]);

        $response = $this->get(route('shop.index', ['q' => 'ذهبية']));

        // Default locale is Arabic, so the product card renders name_ar, not
        // name_en — assert against what's actually displayed.
        $response->assertOk();
        $response->assertSee('عباية ذهبية');
        $response->assertDontSee('فستان أزرق');
    }

    public function test_category_filter_still_works_and_is_not_duplicated(): void
    {
        $categoryA = $this->makeCategory('Category A');
        $categoryB = $this->makeCategory('Category B');
        $this->makeProduct('Product In A', 500, $categoryA);
        $this->makeProduct('Product In B', 500, $categoryB);

        $response = $this->get(route('shop.index', ['category' => $categoryA->slug]));

        $response->assertOk();
        $response->assertSee('Product In A');
        $response->assertDontSee('Product In B');
    }

    public function test_price_range_filter_includes_only_products_within_bounds(): void
    {
        $this->makeProduct('Cheap Item', 100);
        $this->makeProduct('Mid Item', 500);
        $this->makeProduct('Expensive Item', 2000);

        $response = $this->get(route('shop.index', ['min_price' => 200, 'max_price' => 1000]));

        $response->assertOk();
        $response->assertSee('Mid Item');
        $response->assertDontSee('Cheap Item');
        $response->assertDontSee('Expensive Item');
    }

    public function test_price_range_with_min_greater_than_max_returns_empty_state_not_an_error(): void
    {
        $this->makeProduct('Only Product', 500);

        $response = $this->get(route('shop.index', ['min_price' => 900, 'max_price' => 100]));

        $response->assertOk();
        $response->assertDontSee('Only Product');
        $response->assertSee('معلش، مش لاقيين قطع تطابق بحثك');
    }

    public function test_size_filter_only_returns_products_with_that_size_in_stock(): void
    {
        $this->makeProduct('Product Size M', 500, size: 'M', stock: 5);
        $this->makeProduct('Product Size L', 500, size: 'L', stock: 5);

        $response = $this->get(route('shop.index', ['size' => 'M']));

        $response->assertOk();
        $response->assertSee('Product Size M');
        $response->assertDontSee('Product Size L');
    }

    public function test_size_filter_options_exclude_out_of_stock_sizes(): void
    {
        $this->makeProduct('In Stock Product', 500, size: 'M', stock: 5);
        $this->makeProduct('Out Of Stock Product', 500, size: 'XL', stock: 0);

        $response = $this->get(route('shop.index'));

        $response->assertOk();
        $response->assertSee('value="M"', false);
        $response->assertDontSee('value="XL"', false);
    }

    public function test_sort_price_low_to_high(): void
    {
        $this->makeProduct('Expensive First', 2000);
        $this->makeProduct('Cheap Second', 100);

        $response = $this->get(route('shop.index', ['sort' => 'price_asc']));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertLessThan(
            strpos($content, 'Expensive First'),
            strpos($content, 'Cheap Second'),
            'Expected the cheaper product to render before the more expensive one.'
        );
    }

    public function test_sort_price_high_to_low(): void
    {
        $this->makeProduct('Cheap First', 100);
        $this->makeProduct('Expensive Second', 2000);

        $response = $this->get(route('shop.index', ['sort' => 'price_desc']));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertLessThan(
            strpos($content, 'Cheap First'),
            strpos($content, 'Expensive Second'),
            'Expected the more expensive product to render before the cheaper one.'
        );
    }

    public function test_sort_defaults_to_newest_first(): void
    {
        $older = $this->makeProduct('Older Product', 500);
        $older->created_at = now()->subDay();
        $older->save();

        $newer = $this->makeProduct('Newer Product', 500);
        $newer->created_at = now();
        $newer->save();

        $response = $this->get(route('shop.index'));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertLessThan(
            strpos($content, 'Older Product'),
            strpos($content, 'Newer Product'),
            'Expected the newest product to render first by default.'
        );
    }

    public function test_combined_search_category_price_and_size_filters_narrow_correctly(): void
    {
        $category = $this->makeCategory('Combined Category');
        $other = $this->makeCategory('Other Category');

        // Matches every filter — should appear.
        $this->makeProduct('Golden Belt Deluxe', 600, $category, size: 'M', stock: 3);
        // Wrong category.
        $this->makeProduct('Golden Belt Wrong Category', 600, $other, size: 'M', stock: 3);
        // Wrong price (outside range).
        $this->makeProduct('Golden Belt Too Expensive', 5000, $category, size: 'M', stock: 3);
        // Wrong size.
        $this->makeProduct('Golden Belt Wrong Size', 600, $category, size: 'L', stock: 3);
        // Doesn't match search term at all.
        $this->makeProduct('Unrelated Product', 600, $category, size: 'M', stock: 3);

        $response = $this->get(route('shop.index', [
            'q' => 'Golden Belt',
            'category' => $category->slug,
            'min_price' => 100,
            'max_price' => 1000,
            'size' => 'M',
        ]));

        $response->assertOk();
        $response->assertSee('Golden Belt Deluxe');
        $response->assertDontSee('Golden Belt Wrong Category');
        $response->assertDontSee('Golden Belt Too Expensive');
        $response->assertDontSee('Golden Belt Wrong Size');
        $response->assertDontSee('Unrelated Product');
    }

    public function test_empty_result_shows_arabic_friendly_message_not_a_blank_page(): void
    {
        $this->makeProduct('Something Else', 500);

        $response = $this->get(route('shop.index', ['q' => 'nonexistent-product-xyz']));

        $response->assertOk();
        $response->assertDontSee('Something Else');
        $response->assertSee('معلش، مش لاقيين قطع تطابق بحثك');
        $response->assertSee(__('View All Products'));
    }

    public function test_filters_are_preserved_across_pagination_and_url_is_shareable(): void
    {
        $category = $this->makeCategory('Paginated Category');
        for ($i = 1; $i <= 15; $i++) {
            $this->makeProduct("Paginated Product {$i}", 500, $category);
        }

        $response = $this->get(route('shop.index', ['category' => $category->slug, 'sort' => 'price_asc', 'page' => 2]));

        $response->assertOk();
        // withQueryString() should carry category+sort into the pagination links.
        $response->assertSee('category='.$category->slug, false);
        $response->assertSee('sort=price_asc', false);
    }

    public function test_shop_index_response_is_never_cached(): void
    {
        $category = $this->makeCategory('Cache Check Category');
        $this->makeProduct('Cache Check Product', 500, $category);

        $this->get(route('shop.index', ['category' => $category->slug]))
            ->assertOk()
            ->assertSee('Cache Check Product');

        // If the filtered listing were cached, deleting the product would
        // leave a stale row behind on the next request.
        Product::where('name_en', 'Cache Check Product')->delete();

        $this->get(route('shop.index', ['category' => $category->slug]))
            ->assertOk()
            ->assertDontSee('Cache Check Product');
    }
}
