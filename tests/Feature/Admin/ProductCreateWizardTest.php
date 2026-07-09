<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductCreateWizardTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    public function test_store_redirects_into_guided_setup_on_the_edit_page(): void
    {
        $admin = $this->makeAdmin();
        $category = Category::create(['name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-1', 'is_active' => true, 'sort_order' => 1]);

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name_ar' => 'منتج', 'name_en' => 'New Product',
            'price' => 300, 'status' => 'draft',
        ]);

        $product = Product::where('name_en', 'New Product')->firstOrFail();
        $response->assertRedirect(route('admin.products.edit', ['product' => $product, 'wizard' => 1]));
    }

    public function test_edit_page_shows_guided_setup_progress_when_wizard_flag_is_present(): void
    {
        $admin = $this->makeAdmin();
        $category = Category::create(['name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-1', 'is_active' => true, 'sort_order' => 1]);
        $product = Product::create(['category_id' => $category->id, 'name_ar' => 'منتج', 'name_en' => 'Product', 'slug' => 'product-1', 'price' => 300, 'is_active' => false, 'is_featured' => false, 'status' => 'draft']);

        $withWizard = $this->actingAs($admin)->get(route('admin.products.edit', ['product' => $product, 'wizard' => 1]));
        $withWizard->assertOk()->assertSee(__('product_options.switch_to_classic'));

        $withoutWizard = $this->actingAs($admin)->get(route('admin.products.edit', $product));
        $withoutWizard->assertOk()->assertSee(__('product_options.switch_to_wizard'));
    }

    public function test_review_step_publish_button_is_present_and_publishing_uses_the_bulk_action_endpoint(): void
    {
        $admin = $this->makeAdmin();
        $category = Category::create(['name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-1', 'is_active' => true, 'sort_order' => 1]);
        $product = Product::create(['category_id' => $category->id, 'name_ar' => 'منتج', 'name_en' => 'Product', 'slug' => 'product-1', 'price' => 300, 'is_active' => false, 'is_featured' => false, 'status' => 'draft']);

        $this->actingAs($admin)->get(route('admin.products.edit', ['product' => $product, 'wizard' => 1]))
            ->assertOk()
            ->assertSee(__('product_options.publish_now'));

        $this->actingAs($admin)
            ->postJson(route('admin.products.bulk-action'), ['action' => 'publish', 'ids' => [$product->id]])
            ->assertOk();

        $this->assertTrue($product->refresh()->is_active);
        $this->assertSame(Product::STATUS_PUBLISHED, $product->status);
    }
}
