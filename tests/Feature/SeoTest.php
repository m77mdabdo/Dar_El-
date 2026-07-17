<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeoTest extends TestCase
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

    protected function makeProduct(string $name = 'Product', bool $active = true, int $price = 1000): Product
    {
        $product = Product::create([
            'category_id' => $this->defaultCategory()->id,
            'name_ar' => $name, 'name_en' => $name, 'slug' => Str::slug($name).'-'.uniqid(),
            'price' => $price, 'is_active' => $active, 'is_featured' => false,
        ]);
        $product->sizes()->create(['size' => 'M', 'stock' => 5]);

        return $product;
    }

    // ---------------------------------------------------------------
    // Sitemap
    // ---------------------------------------------------------------

    public function test_sitemap_returns_valid_xml(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/xml; charset=UTF-8');

        $dom = new \DOMDocument();
        $isValid = @$dom->loadXML($response->getContent());
        $this->assertTrue($isValid, 'Sitemap response is not valid XML.');
    }

    public function test_sitemap_includes_core_static_pages(): void
    {
        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertSee(route('home'), false);
        $response->assertSee(route('shop.index'), false);
        $response->assertSee(route('about'), false);
        $response->assertSee(route('contact.show'), false);
    }

    public function test_sitemap_includes_active_products_only(): void
    {
        $active = $this->makeProduct('Active Sitemap Product', active: true);
        $inactive = $this->makeProduct('Inactive Sitemap Product', active: false);

        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertSee(route('shop.show', $active), false);
        $response->assertDontSee(route('shop.show', $inactive), false);
    }

    public function test_sitemap_includes_published_blog_posts_only(): void
    {
        $published = BlogPost::create([
            'title_ar' => 'منشور', 'title_en' => 'Published Post', 'slug' => 'published-post-'.uniqid(),
            'excerpt_ar' => 'e', 'excerpt_en' => 'e', 'body_ar' => 'b', 'body_en' => 'b',
            'is_published' => true, 'published_at' => now(),
        ]);
        $draft = BlogPost::create([
            'title_ar' => 'مسودة', 'title_en' => 'Draft Post', 'slug' => 'draft-post-'.uniqid(),
            'excerpt_ar' => 'e', 'excerpt_en' => 'e', 'body_ar' => 'b', 'body_en' => 'b',
            'is_published' => false,
        ]);

        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertSee(route('blog.show', $published), false);
        $response->assertDontSee(route('blog.show', $draft), false);
    }

    public function test_sitemap_reflects_a_newly_created_product(): void
    {
        $this->get(route('sitemap'))->assertOk()->assertDontSee('brand-new-sitemap-product', false);

        $product = $this->makeProduct('Brand New Sitemap Product');

        // Cached for an hour, but must still reflect same-day — cache is
        // busted by Product's own saved()/deleted() model events, the same
        // mechanism already proven for storefront.home.data.
        $response = $this->get(route('sitemap'));
        $response->assertOk();
        $response->assertSee(route('shop.show', $product), false);
    }

    public function test_sitemap_includes_active_categories(): void
    {
        $category = $this->defaultCategory();

        $response = $this->get(route('sitemap'));

        $response->assertOk();
        $response->assertSee('category='.$category->slug, false);
    }

    // ---------------------------------------------------------------
    // Canonical URLs
    // ---------------------------------------------------------------

    public function test_plain_shop_page_canonicalizes_to_itself(): void
    {
        $response = $this->get(route('shop.index'));

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.route('shop.index').'">', false);
    }

    public function test_category_only_filter_canonicalizes_to_the_category_url(): void
    {
        $category = $this->defaultCategory();

        $response = $this->get(route('shop.index', ['category' => $category->slug]));

        $response->assertOk();
        $expected = route('shop.index', ['category' => $category->slug]);
        $response->assertSee('<link rel="canonical" href="'.$expected.'">', false);
    }

    public function test_filters_without_a_category_canonicalize_to_the_plain_shop_page(): void
    {
        $this->makeProduct('Filter Canonical Product');

        $response = $this->get(route('shop.index', ['q' => 'Filter', 'sort' => 'price_asc', 'min_price' => 100]));

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.route('shop.index').'">', false);
    }

    public function test_category_plus_other_filters_still_canonicalizes_to_the_category_only_url(): void
    {
        $category = $this->defaultCategory();

        $response = $this->get(route('shop.index', [
            'category' => $category->slug, 'sort' => 'price_asc', 'min_price' => 100, 'page' => 2,
        ]));

        $response->assertOk();
        $expected = route('shop.index', ['category' => $category->slug]);
        $response->assertSee('<link rel="canonical" href="'.$expected.'">', false);
    }

    public function test_product_page_canonicalizes_to_its_own_clean_url(): void
    {
        $product = $this->makeProduct('Canonical Product');

        $response = $this->get(route('shop.show', $product));

        $response->assertOk();
        $response->assertSee('<link rel="canonical" href="'.route('shop.show', $product).'">', false);
    }

    // ---------------------------------------------------------------
    // Product structured data (JSON-LD)
    // ---------------------------------------------------------------

    protected function extractJsonLd(string $html, string $type): ?array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $html, $matches);

        foreach ($matches[1] as $block) {
            $decoded = json_decode(trim($block), true);
            if (($decoded['@type'] ?? null) === $type) {
                return $decoded;
            }
        }

        return null;
    }

    public function test_product_schema_is_valid_json_with_correct_price_and_in_stock_availability(): void
    {
        $product = $this->makeProduct('Schema Product', price: 1500);

        $response = $this->get(route('shop.show', $product));
        $response->assertOk();

        $schema = $this->extractJsonLd($response->getContent(), 'Product');

        $this->assertNotNull($schema, 'Product JSON-LD schema not found or not valid JSON.');
        $this->assertSame('Schema Product', $schema['name']);
        $this->assertSame('1500', $schema['offers']['price']);
        $this->assertSame('EGP', $schema['offers']['priceCurrency']);
        $this->assertSame('https://schema.org/InStock', $schema['offers']['availability']);
    }

    public function test_product_schema_reflects_out_of_stock_availability(): void
    {
        $product = Product::create([
            'category_id' => $this->defaultCategory()->id,
            'name_ar' => 'Out Of Stock Schema', 'name_en' => 'Out Of Stock Schema',
            'slug' => 'oos-schema-'.uniqid(), 'price' => 800, 'is_active' => true, 'is_featured' => false,
        ]);
        $product->sizes()->create(['size' => 'M', 'stock' => 0]);

        $response = $this->get(route('shop.show', $product));
        $response->assertOk();

        $schema = $this->extractJsonLd($response->getContent(), 'Product');

        $this->assertSame('https://schema.org/OutOfStock', $schema['offers']['availability']);
    }

    public function test_organization_schema_renders_on_every_storefront_page(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $schema = $this->extractJsonLd($response->getContent(), 'Organization');
        $this->assertNotNull($schema);
        $this->assertSame([], $schema['sameAs']);
    }

    // ---------------------------------------------------------------
    // Meta title/description fallback
    // ---------------------------------------------------------------

    public function test_product_meta_title_falls_back_to_name_when_seo_override_is_empty(): void
    {
        $product = $this->makeProduct('Fallback Title Product');

        $response = $this->get(route('shop.show', $product));

        $response->assertOk();
        $response->assertSee('<title>Fallback Title Product — Dar El Jamila</title>', false);
    }

    public function test_product_meta_title_uses_seo_override_when_set(): void
    {
        // name_ar/name_en (via makeProduct) and meta_title_ar/en set to the
        // same value here — the default storefront locale is Arabic, so
        // seoTitle() reads *_ar; this test is about override-vs-fallback
        // logic, not AR/EN rendering, so both locales carry identical text.
        $product = $this->makeProduct('Product With Override');
        $product->update(['meta_title_ar' => 'Custom SEO Title', 'meta_title_en' => 'Custom SEO Title']);

        $response = $this->get(route('shop.show', $product));

        $response->assertOk();
        $response->assertSee('<title>Custom SEO Title — Dar El Jamila</title>', false);
        $response->assertDontSee('<title>Product With Override — Dar El Jamila</title>', false);
    }

    public function test_product_meta_description_falls_back_to_description_when_seo_override_is_empty(): void
    {
        $product = $this->makeProduct('Desc Fallback Product');
        $product->update([
            'description_ar' => 'A fallback product description for testing.',
            'description_en' => 'A fallback product description for testing.',
        ]);

        $response = $this->get(route('shop.show', $product));

        $response->assertOk();
        $response->assertSee('A fallback product description for testing.', false);
    }

    public function test_product_meta_description_uses_seo_override_when_set(): void
    {
        $product = $this->makeProduct('Desc Override Product');
        $product->update([
            'description_ar' => 'A generic description that should NOT appear.',
            'description_en' => 'A generic description that should NOT appear.',
            'meta_description_ar' => 'A custom SEO meta description.',
            'meta_description_en' => 'A custom SEO meta description.',
        ]);

        $response = $this->get(route('shop.show', $product));

        $response->assertOk();
        $response->assertSee('<meta name="description" content="A custom SEO meta description.">', false);
        // The raw description legitimately still appears elsewhere on the
        // page (the product's own description section) — only the <meta>
        // tag itself is expected to prefer the SEO override.
        $response->assertDontSee('<meta name="description" content="A generic description that should NOT appear.">', false);
    }
}
