<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ArabicSearchNormalizationTest extends TestCase
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

    protected function makeProduct(string $nameAr): Product
    {
        return Product::create([
            'category_id' => $this->defaultCategory()->id,
            'name_ar' => $nameAr, 'name_en' => 'placeholder-'.uniqid(),
            'slug' => Str::slug('product-'.uniqid()),
            'price' => 500, 'is_active' => true, 'is_featured' => false,
        ]);
    }

    /**
     * Asserts a product matches via Product::searchByName() (proving the
     * shared scope itself works) AND via both public endpoints that use
     * it — /shop?q= and /search/live?q= — so a fix that only touched one
     * call site, or a regression that broke the "shared" part of the
     * scope, would be caught here rather than assumed.
     */
    protected function assertMatchesEverywhere(Product $product, string $query): void
    {
        $this->assertTrue(
            Product::searchByName($query)->whereKey($product->id)->exists(),
            "Expected Product::searchByName('{$query}') to match product #{$product->id} ({$product->name_ar})."
        );

        $shopResponse = $this->get(route('shop.index', ['q' => $query]));
        $shopResponse->assertOk();
        $shopResponse->assertSee($product->name_ar);

        $liveResponse = $this->getJson(route('search.live', ['q' => $query]));
        $liveResponse->assertOk();
        $names = collect($liveResponse->json('results'))->pluck('name')->all();
        $this->assertContains($product->name_ar, $names, "Expected /search/live?q={$query} to include \"{$product->name_ar}\".");
    }

    public function test_hamza_variants_match_regardless_of_form(): void
    {
        // The exact example from the request: stored with hamza-below on
        // alef (إسدال), customer types plain alef (اسدال).
        $product = $this->makeProduct('إسدال');

        $this->assertMatchesEverywhere($product, 'اسدال');
    }

    public function test_hamza_variants_match_in_the_other_direction_too(): void
    {
        // Stored WITHOUT hamza, customer types WITH hamza-above.
        $product = $this->makeProduct('اناقة');

        $this->assertMatchesEverywhere($product, 'أناقة');
    }

    public function test_bare_hamza_normalizes_to_alef(): void
    {
        $product = $this->makeProduct('مسألة أناقة');

        $this->assertMatchesEverywhere($product, 'مسالة اناقة');
    }

    public function test_alef_maksura_matches_yaa(): void
    {
        // Stored with alef maksura (ى), customer types yaa (ي).
        $product = $this->makeProduct('فستان ملكى');

        $this->assertMatchesEverywhere($product, 'فستان ملكي');
    }

    public function test_taa_marbuta_matches_haa(): void
    {
        // Stored with taa marbuta (ة), customer types haa (ه).
        $product = $this->makeProduct('عباية فاخرة');

        $this->assertMatchesEverywhere($product, 'عبايه فاخره');
    }

    public function test_diacritics_are_stripped_before_matching(): void
    {
        // Stored WITH full tashkeel, customer types plain (undiacritized).
        $product = $this->makeProduct('عَبَايَة');

        $this->assertMatchesEverywhere($product, 'عباية');
    }

    public function test_tatweel_is_stripped_before_matching(): void
    {
        // Stored with tatweel/kashida elongation inserted mid-word.
        $product = $this->makeProduct('عبـــاية');

        $this->assertMatchesEverywhere($product, 'عباية');
    }

    public function test_combination_of_variations_still_matches(): void
    {
        // Hamza + taa marbuta + diacritics all at once.
        $product = $this->makeProduct('إِسْدَال أَنيق');

        $this->assertMatchesEverywhere($product, 'اسدال انيق');
    }

    public function test_exact_match_still_works_unchanged(): void
    {
        $product = $this->makeProduct('عباية كلاسيكية');

        $this->assertMatchesEverywhere($product, 'عباية كلاسيكية');
    }

    public function test_normalization_does_not_cause_false_positive_matches(): void
    {
        $this->makeProduct('إسدال');
        $unrelated = $this->makeProduct('فستان سهرة');

        $this->assertFalse(
            Product::searchByName('اسدال')->whereKey($unrelated->id)->exists(),
            'Normalization should not make an unrelated product match.'
        );
    }
}
