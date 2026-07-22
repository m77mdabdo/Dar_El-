<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Notifications\ProductLowStock;
use App\Notifications\ProductOutOfStock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Before this, the admin's Variants tab (product_variants) looked like a
 * complete stock-management screen but is never read by the storefront/
 * cart/checkout, which use product_sizes exclusively — and the one code
 * path that would edit product_sizes, ProductController::syncSizes(), had
 * no form anywhere feeding it. Every test below exercises the real,
 * newly-connected admin.products.sizes.update route (the fix), and the
 * warning-banner test proves staff are now told, in the one place they'd
 * otherwise be misled, that the Variants tab isn't the real thing.
 */
class ProductSizesStockTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    protected function makeProduct(): Product
    {
        $category = Category::create(['name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);

        return Product::create(['category_id' => $category->id, 'name_ar' => 'منتج', 'name_en' => 'Product', 'slug' => 'product-'.uniqid(), 'price' => 300, 'is_active' => true, 'is_featured' => false]);
    }

    // ---------------------------------------------------------------
    // The warning banner
    // ---------------------------------------------------------------

    public function test_the_variants_tab_shows_a_warning_that_it_does_not_control_real_stock(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();

        $response = $this->actingAs($admin)->get(route('admin.products.edit', $product));

        $response->assertOk();
        $response->assertSee(__('product_options.variants_dev_warning'));
    }

    public function test_the_sizes_and_stock_tab_is_present_on_the_edit_page(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();
        $product->sizes()->create(['size' => 'M', 'stock' => 10]);

        $response = $this->actingAs($admin)->get(route('admin.products.edit', $product));

        $response->assertOk();
        $response->assertSee(__('products.sizes_stock'));
        $response->assertSee(route('admin.products.sizes.update', $product), false);
    }

    // ---------------------------------------------------------------
    // The real, storefront-affecting stock editor
    // ---------------------------------------------------------------

    public function test_admin_can_update_stock_for_an_existing_size(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 10]);

        $response = $this->actingAs($admin)->patch(route('admin.products.sizes.update', $product), [
            'sizes' => ['M' => 20],
        ]);

        $response->assertRedirect();
        $this->assertSame(20, $size->fresh()->stock);
    }

    public function test_admin_can_add_a_brand_new_size(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();

        $this->assertSame(0, $product->sizes()->count());

        $response = $this->actingAs($admin)->patch(route('admin.products.sizes.update', $product), [
            'new_size_name' => 'L',
            'new_size_stock' => 7,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('product_sizes', ['product_id' => $product->id, 'size' => 'L', 'stock' => 7]);
    }

    public function test_negative_stock_is_floored_at_zero(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 10]);

        $this->actingAs($admin)->patch(route('admin.products.sizes.update', $product), [
            'sizes' => ['M' => -5],
        ]);

        $this->assertSame(0, $size->fresh()->stock);
    }

    public function test_updating_stock_here_still_triggers_the_low_stock_alert(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 10]);

        $this->actingAs($admin)->patch(route('admin.products.sizes.update', $product), [
            'sizes' => ['M' => 3],
        ]);

        $this->assertSame(3, $size->fresh()->stock);
        Notification::assertSentTo(User::admins(), ProductLowStock::class);
    }

    public function test_updating_stock_here_still_triggers_the_out_of_stock_alert(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();
        $size = $product->sizes()->create(['size' => 'M', 'stock' => 10]);

        $this->actingAs($admin)->patch(route('admin.products.sizes.update', $product), [
            'sizes' => ['M' => 0],
        ]);

        $this->assertSame(0, $size->fresh()->stock);
        Notification::assertSentTo(User::admins(), ProductOutOfStock::class);
    }

    public function test_a_new_size_name_over_50_characters_is_rejected(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();

        $response = $this->actingAs($admin)->patch(route('admin.products.sizes.update', $product), [
            'new_size_name' => str_repeat('a', 51),
            'new_size_stock' => 5,
        ]);

        $response->assertSessionHasErrors('new_size_name');
        $this->assertSame(0, $product->sizes()->count());
    }

    public function test_non_admin_is_forbidden(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(Role::findOrCreate('customer', 'web'));
        $product = $this->makeProduct();
        $product->sizes()->create(['size' => 'M', 'stock' => 10]);

        $this->actingAs($customer)->patch(route('admin.products.sizes.update', $product), [
            'sizes' => ['M' => 99],
        ])->assertForbidden();

        $this->assertSame(10, $product->sizes()->first()->stock);
    }
}
