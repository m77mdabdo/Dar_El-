<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductManagementPolishTest extends TestCase
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

    public function test_flash_status_message_is_rendered_for_the_toast_script_to_pick_up(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();

        $response = $this->actingAs($admin)->from(route('admin.products.index'))
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect();

        $follow = $this->actingAs($admin)->get($response->headers->get('Location'));
        $follow->assertSee('data-flash-toast="success"', false);
        $follow->assertSee(__('products.deleted'));
    }

    public function test_variant_grid_is_not_marked_as_a_large_grid_below_the_threshold(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();
        $option = $product->options()->create(['name_ar' => 'اللون', 'name_en' => 'Color', 'sort_order' => 0]);
        $value = $option->values()->create(['name_ar' => 'أحمر', 'name_en' => 'Red', 'sort_order' => 0, 'is_active' => true]);
        $variant = $product->variants()->create(['stock' => 1, 'is_active' => true]);
        $variant->values()->attach($value->id);

        $response = $this->actingAs($admin)->get(route('admin.products.edit', $product));

        $response->assertOk();
        $response->assertDontSee('data-large-grid', false);
    }

    public function test_variant_grid_is_marked_as_a_large_grid_above_the_threshold(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();
        $option = $product->options()->create(['name_ar' => 'اللون', 'name_en' => 'Color', 'sort_order' => 0]);

        foreach (range(1, 201) as $i) {
            $value = $option->values()->create(['name_ar' => "قيمة{$i}", 'name_en' => "Value{$i}", 'sort_order' => $i, 'is_active' => true]);
            $variant = $product->variants()->create(['stock' => 1, 'is_active' => true]);
            $variant->values()->attach($value->id);
        }

        $response = $this->actingAs($admin)->get(route('admin.products.edit', $product));

        $response->assertOk();
        $response->assertSee('data-large-grid', false);
    }
}
