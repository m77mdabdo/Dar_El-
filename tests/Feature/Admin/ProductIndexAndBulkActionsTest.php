<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductIndexAndBulkActionsTest extends TestCase
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
        ], $overrides));
    }

    public function test_non_admin_forbidden_from_bulk_action(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(Role::findOrCreate('customer', 'web'));
        $product = $this->makeProduct();

        $this->actingAs($customer)
            ->postJson(route('admin.products.bulk-action'), ['action' => 'archive', 'ids' => [$product->id]])
            ->assertForbidden();
    }

    public function test_index_search_matches_name_ar_name_en_and_sku(): void
    {
        $admin = $this->makeAdmin();
        $byEn = $this->makeProduct(['name_en' => 'Silk Abaya', 'name_ar' => 'شيء', 'sku' => 'SKU-1']);
        $byAr = $this->makeProduct(['name_en' => 'Other', 'name_ar' => 'عباية حرير', 'sku' => 'SKU-2']);
        $bySku = $this->makeProduct(['name_en' => 'Another', 'name_ar' => 'آخر', 'sku' => 'UNIQUE-SKU']);
        $noMatch = $this->makeProduct(['name_en' => 'Unrelated', 'name_ar' => 'غير ذلك', 'sku' => 'NOPE']);

        $response = $this->actingAs($admin)->get(route('admin.products.index', ['search' => 'Silk']));
        $response->assertSee('Silk Abaya')->assertDontSee('Unrelated');

        $response = $this->actingAs($admin)->get(route('admin.products.index', ['search' => 'حرير']));
        $response->assertSee('Other');

        $response = $this->actingAs($admin)->get(route('admin.products.index', ['search' => 'UNIQUE-SKU']));
        $response->assertSee('Another');
    }

    public function test_index_filters_by_status(): void
    {
        $admin = $this->makeAdmin();
        $published = $this->makeProduct(['name_en' => 'Published One', 'status' => Product::STATUS_PUBLISHED]);
        $draft = $this->makeProduct(['name_en' => 'Draft One', 'status' => Product::STATUS_DRAFT, 'is_active' => false]);

        $response = $this->actingAs($admin)->get(route('admin.products.index', ['status' => 'draft']));
        $response->assertSee('Draft One')->assertDontSee('Published One');
    }

    public function test_bulk_publish_sets_status_and_is_active(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(['status' => Product::STATUS_DRAFT, 'is_active' => false]);

        $this->actingAs($admin)
            ->postJson(route('admin.products.bulk-action'), ['action' => 'publish', 'ids' => [$product->id]])
            ->assertOk();

        $product->refresh();
        $this->assertSame(Product::STATUS_PUBLISHED, $product->status);
        $this->assertTrue($product->is_active);
    }

    public function test_bulk_archive_sets_status_and_is_active(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(['status' => Product::STATUS_PUBLISHED, 'is_active' => true]);

        $this->actingAs($admin)
            ->postJson(route('admin.products.bulk-action'), ['action' => 'archive', 'ids' => [$product->id]])
            ->assertOk();

        $product->refresh();
        $this->assertSame(Product::STATUS_ARCHIVED, $product->status);
        $this->assertFalse($product->is_active);
    }

    public function test_bulk_delete_removes_only_selected_and_deletes_gallery_files(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();
        $keep = $this->makeProduct(['name_en' => 'Keep Me']);
        $remove = $this->makeProduct(['name_en' => 'Remove Me']);

        $image = $remove->images()->create([
            'path' => UploadedFile::fake()->image('gallery.jpg')->store("products/{$remove->id}", 'public'),
            'sort_order' => 0,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.products.bulk-action'), ['action' => 'delete', 'ids' => [$remove->id]])
            ->assertOk();

        $this->assertDatabaseMissing('products', ['id' => $remove->id]);
        $this->assertDatabaseHas('products', ['id' => $keep->id]);
        Storage::disk('public')->assertMissing($image->path);
    }

    public function test_bulk_duplicate_creates_a_draft_copy_with_unique_slug_and_copied_data(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();
        $product = $this->makeProduct(['name_en' => 'Silk Abaya', 'status' => Product::STATUS_PUBLISHED, 'is_active' => true]);
        $product->sizes()->create(['size' => 'M', 'stock' => 5]);

        $this->actingAs($admin)
            ->postJson(route('admin.products.bulk-action'), ['action' => 'duplicate', 'ids' => [$product->id]])
            ->assertOk();

        $this->assertSame(2, Product::count());
        $copy = Product::where('id', '!=', $product->id)->first();
        $this->assertNotSame($product->slug, $copy->slug);
        $this->assertSame(Product::STATUS_DRAFT, $copy->status);
        $this->assertFalse($copy->is_active);
        $this->assertSame(1, $copy->sizes()->count());
    }

    public function test_bulk_action_rejects_unknown_action_and_empty_ids(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();

        $this->actingAs($admin)
            ->postJson(route('admin.products.bulk-action'), ['action' => 'nonsense', 'ids' => [$product->id]])
            ->assertJsonValidationErrors('action');

        $this->actingAs($admin)
            ->postJson(route('admin.products.bulk-action'), ['action' => 'archive', 'ids' => []])
            ->assertJsonValidationErrors('ids');
    }
}
