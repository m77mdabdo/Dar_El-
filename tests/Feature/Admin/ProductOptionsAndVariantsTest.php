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

class ProductOptionsAndVariantsTest extends TestCase
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

    public function test_non_admin_is_forbidden(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(Role::findOrCreate('customer', 'web'));
        $product = $this->makeProduct();

        $this->actingAs($customer)->post(route('admin.products.options.store', $product), ['name_ar' => 'ل', 'name_en' => 'Color'])->assertForbidden();
    }

    public function test_admin_can_create_an_option_with_values(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();

        $this->actingAs($admin)->post(route('admin.products.options.store', $product), [
            'name_ar' => 'اللون', 'name_en' => 'Color',
        ])->assertRedirect();

        $this->assertDatabaseHas('product_options', ['product_id' => $product->id, 'name_en' => 'Color']);

        $option = $product->options()->first();

        $this->actingAs($admin)->post(route('admin.products.options.values.store', [$product, $option]), [
            'name_ar' => 'أحمر', 'name_en' => 'Red', 'hex_color' => '#FF0000',
        ])->assertRedirect();

        $this->assertDatabaseHas('product_option_values', ['product_option_id' => $option->id, 'name_en' => 'Red', 'hex_color' => '#FF0000']);
    }

    public function test_generate_variants_creates_the_full_combination_and_is_idempotent(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();

        $color = $product->options()->create(['name_ar' => 'اللون', 'name_en' => 'Color', 'sort_order' => 0]);
        $color->values()->create(['name_ar' => 'أحمر', 'name_en' => 'Red', 'sort_order' => 0, 'is_active' => true]);
        $color->values()->create(['name_ar' => 'أزرق', 'name_en' => 'Blue', 'sort_order' => 1, 'is_active' => true]);

        $size = $product->options()->create(['name_ar' => 'المقاس', 'name_en' => 'Size', 'sort_order' => 1]);
        $size->values()->create(['name_ar' => 'S', 'name_en' => 'S', 'sort_order' => 0, 'is_active' => true]);
        $size->values()->create(['name_ar' => 'M', 'name_en' => 'M', 'sort_order' => 1, 'is_active' => true]);
        $size->values()->create(['name_ar' => 'L', 'name_en' => 'L', 'sort_order' => 2, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.products.variants.generate', $product))->assertRedirect();

        $this->assertSame(6, $product->variants()->count());

        // Adding a 3rd color and re-generating should only add the 3 new combinations.
        $color->values()->create(['name_ar' => 'أخضر', 'name_en' => 'Green', 'sort_order' => 2, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.products.variants.generate', $product))->assertRedirect();

        $this->assertSame(9, $product->variants()->count());
    }

    public function test_generate_variants_rejects_an_oversized_combination(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();

        // 3 options x 30 values each = 27,000 combinations, well over the 500 cap.
        foreach (range(1, 3) as $i) {
            $option = $product->options()->create(['name_ar' => "خيار{$i}", 'name_en' => "Option{$i}", 'sort_order' => $i]);
            foreach (range(1, 30) as $j) {
                $option->values()->create(['name_ar' => "قيمة{$j}", 'name_en' => "Value{$j}", 'sort_order' => $j, 'is_active' => true]);
            }
        }

        $this->actingAs($admin)->post(route('admin.products.variants.generate', $product))
            ->assertSessionHasErrors('options');

        $this->assertSame(0, $product->variants()->count());
    }

    public function test_admin_can_bulk_update_variant_stock_and_price(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();
        $option = $product->options()->create(['name_ar' => 'اللون', 'name_en' => 'Color', 'sort_order' => 0]);
        $value = $option->values()->create(['name_ar' => 'أحمر', 'name_en' => 'Red', 'sort_order' => 0, 'is_active' => true]);
        $variant = $product->variants()->create(['stock' => 0, 'is_active' => true]);
        $variant->values()->attach($value->id);

        $this->actingAs($admin)->patch(route('admin.products.variants.bulk', $product), [
            'variants' => [
                ['id' => $variant->id, 'stock' => 42, 'price_override' => 350, 'sale_price' => 300, 'is_active' => '1'],
            ],
        ])->assertRedirect();

        $variant->refresh();
        $this->assertSame(42, $variant->stock);
        $this->assertSame(350, $variant->price_override);
        $this->assertSame(300, $variant->sale_price);
        $this->assertSame(300, $variant->effective_price);
    }

    public function test_deleting_an_option_value_removes_variants_built_from_it(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();
        $option = $product->options()->create(['name_ar' => 'اللون', 'name_en' => 'Color', 'sort_order' => 0]);
        $value = $option->values()->create(['name_ar' => 'أحمر', 'name_en' => 'Red', 'sort_order' => 0, 'is_active' => true]);
        $variant = $product->variants()->create(['stock' => 5, 'is_active' => true]);
        $variant->values()->attach($value->id);

        $this->actingAs($admin)->delete(route('admin.products.options.values.destroy', [$product, $option, $value]))->assertRedirect();

        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
    }

    public function test_option_value_image_upload_and_delete(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();
        $option = $product->options()->create(['name_ar' => 'اللون', 'name_en' => 'Color', 'sort_order' => 0]);
        $value = $option->values()->create(['name_ar' => 'أحمر', 'name_en' => 'Red', 'sort_order' => 0, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.products.options.values.images.store', [$product, $option, $value]), [
            'images' => [UploadedFile::fake()->image('red-1.jpg')],
        ])->assertRedirect();

        $image = $value->images()->first();
        $this->assertNotNull($image);
        Storage::disk('public')->assertExists($image->path);

        $this->actingAs($admin)->delete(route('admin.products.options.values.images.destroy', [$product, $option, $value, $image]))->assertRedirect();

        Storage::disk('public')->assertMissing($image->path);
        $this->assertDatabaseMissing('product_option_value_images', ['id' => $image->id]);
    }

    public function test_generate_variants_inherits_smart_defaults_and_builds_skus(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();
        $product->update(['sku_prefix' => 'tshirt', 'default_stock' => 10, 'default_low_stock_threshold' => 3, 'weight' => 0.5]);

        $color = $product->options()->create(['name_ar' => 'اللون', 'name_en' => 'Color', 'sort_order' => 0]);
        $color->values()->create(['name_ar' => 'أحمر', 'name_en' => 'Red', 'sort_order' => 0, 'is_active' => true]);
        $size = $product->options()->create(['name_ar' => 'المقاس', 'name_en' => 'Size', 'sort_order' => 1]);
        $size->values()->create(['name_ar' => 'M', 'name_en' => 'M', 'sort_order' => 0, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.products.variants.generate', $product))->assertRedirect();

        $variant = $product->variants()->first();
        $this->assertSame('TSHIRT-RED-M', $variant->sku);
        $this->assertSame(10, $variant->stock);
        $this->assertSame(3, $variant->low_stock_threshold);
        $this->assertSame('0.50', $variant->weight);
    }

    public function test_generate_variants_sku_does_not_collide_with_an_existing_sku(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();
        $product->update(['sku_prefix' => 'tshirt']);

        $color = $product->options()->create(['name_ar' => 'اللون', 'name_en' => 'Color', 'sort_order' => 0]);
        $value = $color->values()->create(['name_ar' => 'أحمر', 'name_en' => 'Red', 'sort_order' => 0, 'is_active' => true]);

        // Pre-existing variant on ANOTHER product occupies the SKU this combo would naturally get.
        $other = $this->makeProduct();
        $other->variants()->create(['sku' => 'TSHIRT-RED', 'stock' => 0, 'is_active' => true]);

        $this->actingAs($admin)->post(route('admin.products.variants.generate', $product))->assertRedirect();

        $variant = $product->variants()->first();
        $this->assertSame('TSHIRT-RED-2', $variant->sku);
    }
}
