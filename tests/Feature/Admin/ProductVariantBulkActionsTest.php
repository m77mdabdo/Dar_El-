<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductVariantBulkActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    protected function makeProductWithVariant(array $variantOverrides = []): array
    {
        $category = Category::create(['name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-'.uniqid(), 'is_active' => true, 'sort_order' => 1]);
        $product = Product::create(['category_id' => $category->id, 'name_ar' => 'منتج', 'name_en' => 'Product', 'slug' => 'product-'.uniqid(), 'price' => 300, 'is_active' => true, 'is_featured' => false]);

        $option = $product->options()->create(['name_ar' => 'اللون', 'name_en' => 'Color', 'sort_order' => 0]);
        $value = $option->values()->create(['name_ar' => 'أحمر', 'name_en' => 'Red', 'sort_order' => 0, 'is_active' => true]);
        $variant = $product->variants()->create(array_merge(['stock' => 5, 'is_active' => true], $variantOverrides));
        $variant->values()->attach($value->id);

        return [$product, $variant];
    }

    public function test_non_admin_forbidden(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(Role::findOrCreate('customer', 'web'));
        [$product, $variant] = $this->makeProductWithVariant();

        $this->actingAs($customer)
            ->postJson(route('admin.products.variants.bulk-action', $product), ['action' => 'activate', 'ids' => [$variant->id]])
            ->assertForbidden();
    }

    public function test_set_stock(): void
    {
        $admin = $this->makeAdmin();
        [$product, $variant] = $this->makeProductWithVariant(['stock' => 5]);

        $this->actingAs($admin)
            ->postJson(route('admin.products.variants.bulk-action', $product), ['action' => 'set_stock', 'ids' => [$variant->id], 'params' => ['stock' => 40]])
            ->assertOk();

        $this->assertSame(40, $variant->refresh()->stock);
    }

    public function test_adjust_stock_never_goes_below_zero(): void
    {
        $admin = $this->makeAdmin();
        [$product, $variant] = $this->makeProductWithVariant(['stock' => 5]);

        $this->actingAs($admin)
            ->postJson(route('admin.products.variants.bulk-action', $product), ['action' => 'adjust_stock', 'ids' => [$variant->id], 'params' => ['delta' => -20]])
            ->assertOk();

        $this->assertSame(0, $variant->refresh()->stock);
    }

    public function test_activate_and_deactivate(): void
    {
        $admin = $this->makeAdmin();
        [$product, $variant] = $this->makeProductWithVariant(['is_active' => true]);

        $this->actingAs($admin)
            ->postJson(route('admin.products.variants.bulk-action', $product), ['action' => 'deactivate', 'ids' => [$variant->id]])
            ->assertOk();
        $this->assertFalse($variant->refresh()->is_active);

        $this->actingAs($admin)
            ->postJson(route('admin.products.variants.bulk-action', $product), ['action' => 'activate', 'ids' => [$variant->id]])
            ->assertOk();
        $this->assertTrue($variant->refresh()->is_active);
    }

    public function test_generate_skus_uses_the_products_sku_prefix(): void
    {
        $admin = $this->makeAdmin();
        [$product, $variant] = $this->makeProductWithVariant();
        $product->update(['sku_prefix' => 'tshirt']);

        $this->actingAs($admin)
            ->postJson(route('admin.products.variants.bulk-action', $product), ['action' => 'generate_skus', 'ids' => [$variant->id]])
            ->assertOk();

        $this->assertSame('TSHIRT-RED', $variant->refresh()->sku);
    }

    public function test_duplicate_creates_a_second_variant_with_the_same_values_and_no_sku(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();
        [$product, $variant] = $this->makeProductWithVariant(['sku' => 'ORIGINAL-SKU', 'stock' => 7]);

        $this->actingAs($admin)
            ->postJson(route('admin.products.variants.bulk-action', $product), ['action' => 'duplicate', 'ids' => [$variant->id]])
            ->assertOk();

        $this->assertSame(2, $product->variants()->count());
        $copy = $product->variants()->where('id', '!=', $variant->id)->first();
        $this->assertNull($copy->sku);
        $this->assertSame(7, $copy->stock);
        $this->assertFalse($copy->is_active);
        $this->assertSame($variant->values->pluck('id')->sort()->values()->all(), $copy->values->pluck('id')->sort()->values()->all());
    }

    public function test_delete_removes_only_selected_variants(): void
    {
        $admin = $this->makeAdmin();
        [$product, $keep] = $this->makeProductWithVariant();
        $remove = $product->variants()->create(['stock' => 1, 'is_active' => true]);

        $this->actingAs($admin)
            ->postJson(route('admin.products.variants.bulk-action', $product), ['action' => 'delete', 'ids' => [$remove->id]])
            ->assertOk();

        $this->assertDatabaseMissing('product_variants', ['id' => $remove->id]);
        $this->assertDatabaseHas('product_variants', ['id' => $keep->id]);
    }

    public function test_rejects_unknown_action_and_missing_params(): void
    {
        $admin = $this->makeAdmin();
        [$product, $variant] = $this->makeProductWithVariant();

        $this->actingAs($admin)
            ->postJson(route('admin.products.variants.bulk-action', $product), ['action' => 'nonsense', 'ids' => [$variant->id]])
            ->assertJsonValidationErrors('action');

        $this->actingAs($admin)
            ->postJson(route('admin.products.variants.bulk-action', $product), ['action' => 'set_stock', 'ids' => [$variant->id]])
            ->assertJsonValidationErrors('params.stock');
    }

    public function test_a_variant_id_belonging_to_another_product_is_ignored(): void
    {
        $admin = $this->makeAdmin();
        [$product, $variant] = $this->makeProductWithVariant();
        [, $otherVariant] = $this->makeProductWithVariant(['stock' => 99]);

        $this->actingAs($admin)
            ->postJson(route('admin.products.variants.bulk-action', $product), ['action' => 'set_stock', 'ids' => [$variant->id, $otherVariant->id], 'params' => ['stock' => 1]])
            ->assertOk();

        $this->assertSame(99, $otherVariant->refresh()->stock);
    }
}
