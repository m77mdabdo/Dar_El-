<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Regression coverage for a real bug: config('cache.serializable_classes')
 * defaults to false, which makes PHP's unserialize() silently discard every
 * object from cache reads (turning them into __PHP_Incomplete_Class stubs)
 * on any driver that actually serializes — database, file, redis. The rest
 * of the suite runs with CACHE_STORE=array (set in phpunit.xml), which never
 * serializes at all and so can't catch this class of bug. These tests force
 * the database driver specifically to exercise a real serialize/unserialize
 * round trip, the same as production.
 */
class CacheSerializationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cache.default' => 'database']);
    }

    protected function makeCategory(string $nameEn = 'Test Category'): Category
    {
        return Category::create([
            'name_ar' => $nameEn, 'name_en' => $nameEn, 'slug' => Str::slug($nameEn).'-'.uniqid(),
            'is_active' => true, 'sort_order' => 1,
        ]);
    }

    public function test_serializable_classes_is_not_globally_disabled(): void
    {
        // The literal regression: 'serializable_classes' => false blocks ALL
        // objects from being unserialized from cache, not just untrusted ones.
        $this->assertNotSame(false, config('cache.serializable_classes'));
    }

    public function test_home_page_categories_survive_a_real_cache_round_trip_on_the_database_driver(): void
    {
        $category = $this->makeCategory('Real Round Trip Category');

        // First request computes fresh and writes to the database cache table.
        $this->get(route('home'))->assertOk()->assertSee('Real Round Trip Category');

        $cached = Cache::get('storefront.categories');
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $cached);
        $this->assertInstanceOf(Category::class, $cached->first());
        $this->assertSame($category->slug, $cached->first()->slug);

        // Second request reads the unserialized value back — this is exactly
        // where __PHP_Incomplete_Class objects surfaced and crashed the page.
        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee('Real Round Trip Category');
        $response->assertSee('category='.$category->slug, false);
    }

    public function test_home_page_featured_products_survive_a_real_cache_round_trip_on_the_database_driver(): void
    {
        $category = $this->makeCategory('Product Round Trip Category');
        $product = Product::create([
            'category_id' => $category->id,
            'name_ar' => 'Round Trip Product', 'name_en' => 'Round Trip Product',
            'slug' => 'round-trip-product-'.uniqid(),
            'price' => 1500, 'is_active' => true, 'is_featured' => true,
        ]);

        $this->get(route('home'))->assertOk()->assertSee('Round Trip Product');

        // Second request must read the SAME data back from cache without
        // throwing and without losing the product's real attributes.
        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee('Round Trip Product');
        $response->assertSee(number_format($product->price));
    }

    public function test_admin_dashboard_recent_lists_survive_a_real_cache_round_trip_on_the_database_driver(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        // First request populates admin.dashboard.summary (recentOrders,
        // recentCustomers, recentMessages — real Eloquent collections).
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        // Second request reads it back from the database cache driver.
        $response = $this->actingAs($admin)->get(route('admin.dashboard'));
        $response->assertOk();
    }
}
