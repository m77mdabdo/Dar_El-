<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class LiveSearchTest extends TestCase
{
    use RefreshDatabase;

    protected ?Category $defaultCategory = null;

    protected function defaultCategory(): Category
    {
        return $this->defaultCategory ??= Category::create([
            'name_ar' => 'General', 'name_en' => 'General', 'slug' => 'general-'.uniqid(),
            'is_active' => true, 'sort_order' => 1,
        ]);
    }

    protected function makeProduct(string $nameEn, string $nameAr, bool $isActive = true): Product
    {
        return Product::create([
            'category_id' => $this->defaultCategory()->id,
            'name_ar' => $nameAr, 'name_en' => $nameEn, 'slug' => Str::slug($nameEn).'-'.uniqid(),
            'price' => 500, 'is_active' => $isActive, 'is_featured' => false,
        ]);
    }

    public function test_partial_match_returns_correct_products_by_english_name(): void
    {
        // name_ar deliberately matches name_en here — this test is about
        // matching, not locale display; the default-Arabic-locale display
        // case is covered separately below.
        $this->makeProduct('Emerald Embroidered Belt', 'Emerald Embroidered Belt');
        $this->makeProduct('Golden Sash', 'Golden Sash');

        $response = $this->getJson(route('search.live', ['q' => 'emb']));

        $response->assertOk();
        $names = collect($response->json('results'))->pluck('name')->all();
        $this->assertContains('Emerald Embroidered Belt', $names);
        $this->assertNotContains('Golden Sash', $names);
    }

    public function test_partial_match_returns_correct_products_by_arabic_name(): void
    {
        $this->makeProduct('Golden Abaya', 'عباية ذهبية');
        $this->makeProduct('Blue Dress', 'فستان أزرق');

        $response = $this->getJson(route('search.live', ['q' => 'ذهبية']));

        $response->assertOk();
        $names = collect($response->json('results'))->pluck('name')->all();
        $this->assertContains('عباية ذهبية', $names);
        $this->assertNotContains('فستان أزرق', $names);
    }

    public function test_inactive_products_are_excluded(): void
    {
        $this->makeProduct('Active Abaya', 'Active Abaya', isActive: true);
        $this->makeProduct('Inactive Abaya', 'Inactive Abaya', isActive: false);

        $response = $this->getJson(route('search.live', ['q' => 'Abaya']));

        $response->assertOk();
        $names = collect($response->json('results'))->pluck('name')->all();
        $this->assertContains('Active Abaya', $names);
        $this->assertNotContains('Inactive Abaya', $names);
        $this->assertSame(1, $response->json('total'));
    }

    public function test_query_shorter_than_two_characters_returns_no_results(): void
    {
        $this->makeProduct('Abaya One', 'عباية واحدة');

        $response = $this->getJson(route('search.live', ['q' => 'a']));

        $response->assertOk();
        $this->assertSame([], $response->json('results'));
        $this->assertSame(0, $response->json('total'));
    }

    public function test_empty_query_returns_no_results(): void
    {
        $this->makeProduct('Abaya One', 'عباية واحدة');

        $response = $this->getJson(route('search.live', ['q' => '']));

        $response->assertOk();
        $this->assertSame([], $response->json('results'));
    }

    public function test_results_are_capped_but_total_reflects_the_real_count(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->makeProduct("Matching Abaya {$i}", "عباية مطابقة {$i}");
        }

        $response = $this->getJson(route('search.live', ['q' => 'Matching Abaya']));

        $response->assertOk();
        $this->assertCount(8, $response->json('results'));
        $this->assertSame(12, $response->json('total'));
    }

    public function test_each_result_includes_name_price_image_and_product_url(): void
    {
        $product = $this->makeProduct('Priced Abaya', 'Priced Abaya');

        $response = $this->getJson(route('search.live', ['q' => 'Priced']));

        $response->assertOk();
        $result = $response->json('results.0');
        $this->assertSame('Priced Abaya', $result['name']);
        $this->assertSame(number_format($product->price).' EGP', $result['price_formatted']);
        $this->assertArrayHasKey('image', $result);
        $this->assertSame(route('shop.show', $product), $result['url']);
    }

    public function test_see_all_results_link_points_to_shop_index_with_the_query(): void
    {
        $this->makeProduct('Searchable Product', 'منتج قابل للبحث');

        $response = $this->getJson(route('search.live', ['q' => 'Searchable']));

        $response->assertOk();
        $this->assertSame(route('shop.index', ['q' => 'Searchable']), $response->json('see_all_url'));
    }

    public function test_live_search_endpoint_is_never_cached(): void
    {
        $product = $this->makeProduct('Cache Check Product', 'Cache Check Product');

        $this->getJson(route('search.live', ['q' => 'Cache Check']))
            ->assertOk()
            ->assertJsonFragment(['name' => 'Cache Check Product']);

        $product->delete();

        $response = $this->getJson(route('search.live', ['q' => 'Cache Check']));
        $response->assertOk();
        $this->assertSame([], $response->json('results'));
    }
}
