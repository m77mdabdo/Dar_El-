<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductAutosaveTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    protected function makeProduct(array $overrides = []): Product
    {
        $category = Category::create(['name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);

        return Product::create(array_merge([
            'category_id' => $category->id, 'name_ar' => 'منتج', 'name_en' => 'Product',
            'slug' => 'product-'.uniqid(), 'price' => 300, 'is_active' => true, 'is_featured' => false,
            'status' => Product::STATUS_PUBLISHED,
        ], $overrides));
    }

    public function test_non_admin_forbidden_from_autosave(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(Role::findOrCreate('customer', 'web'));
        $product = $this->makeProduct();

        $this->actingAs($customer)
            ->patchJson(route('admin.products.autosave', $product), ['name_en' => 'New Name'])
            ->assertForbidden();
    }

    public function test_partial_payload_updates_only_that_field(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(['name_en' => 'Old Name', 'price' => 300]);

        $this->actingAs($admin)
            ->patchJson(route('admin.products.autosave', $product), ['name_en' => 'New Name'])
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        $product->refresh();
        $this->assertSame('New Name', $product->name_en);
        $this->assertSame(300, (int) $product->price);
    }

    public function test_status_field_derives_is_active_via_apply_status(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(['status' => Product::STATUS_DRAFT, 'is_active' => false]);

        $this->actingAs($admin)
            ->patchJson(route('admin.products.autosave', $product), ['status' => 'published'])
            ->assertOk();

        $product->refresh();
        $this->assertSame(Product::STATUS_PUBLISHED, $product->status);
        $this->assertTrue($product->is_active);
        $this->assertNotNull($product->published_at);
    }

    public function test_invalid_partial_payload_returns_422_and_does_not_persist(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(['name_en' => 'Original']);

        $this->actingAs($admin)
            ->patchJson(route('admin.products.autosave', $product), ['name_en' => ''])
            ->assertJsonValidationErrors('name_en');

        $this->assertSame('Original', $product->refresh()->name_en);
    }

    public function test_seo_fields_save_and_fall_back_to_name_and_description_when_blank(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(['name_en' => 'Silk Abaya', 'description_en' => 'A lovely abaya.']);

        $this->assertSame('Silk Abaya', $product->seoTitle('en'));
        $this->assertSame('A lovely abaya.', $product->seoDescription('en'));

        $this->actingAs($admin)
            ->patchJson(route('admin.products.autosave', $product), ['meta_title_en' => 'Custom SEO Title'])
            ->assertOk();

        $product->refresh();
        $this->assertSame('Custom SEO Title', $product->seoTitle('en'));
    }
}
