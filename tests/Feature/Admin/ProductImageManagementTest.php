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

class ProductImageManagementTest extends TestCase
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

    public function test_non_admin_forbidden_from_reorder_and_cover(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(Role::findOrCreate('customer', 'web'));
        $product = $this->makeProduct();
        $image = $product->images()->create(['path' => 'products/1/a.jpg', 'sort_order' => 0]);

        $this->actingAs($customer)
            ->patchJson(route('admin.products.images.reorder', $product), ['ids' => [$image->id]])
            ->assertForbidden();

        $this->actingAs($customer)
            ->patch(route('admin.products.images.cover', [$product, $image]))
            ->assertForbidden();
    }

    public function test_reorder_persists_the_new_sort_order(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();
        $first = $product->images()->create(['path' => 'products/1/a.jpg', 'sort_order' => 0]);
        $second = $product->images()->create(['path' => 'products/1/b.jpg', 'sort_order' => 1]);

        $this->actingAs($admin)
            ->patchJson(route('admin.products.images.reorder', $product), ['ids' => [$second->id, $first->id]])
            ->assertOk();

        $this->assertSame(0, $second->refresh()->sort_order);
        $this->assertSame(1, $first->refresh()->sort_order);
    }

    public function test_reorder_rejects_an_image_id_belonging_to_another_product(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();
        $other = $this->makeProduct();
        $foreignImage = $other->images()->create(['path' => 'products/2/a.jpg', 'sort_order' => 0]);

        $this->actingAs($admin)
            ->patchJson(route('admin.products.images.reorder', $product), ['ids' => [$foreignImage->id]])
            ->assertJsonValidationErrors('ids.0');
    }

    public function test_set_as_cover_copies_the_file_and_updates_image_url(): void
    {
        Storage::fake('public');
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();
        $path = UploadedFile::fake()->image('gallery.jpg')->store("products/{$product->id}", 'public');
        $image = $product->images()->create(['path' => $path, 'sort_order' => 0]);

        $this->actingAs($admin)
            ->patch(route('admin.products.images.cover', [$product, $image]))
            ->assertRedirect();

        $product->refresh();
        $this->assertNotNull($product->image_url);
        $this->assertNotSame($image->path, $product->image_url);
        Storage::disk('public')->assertExists($product->image_url);

        // Deleting the gallery image afterward must not remove the cover file.
        $image->delete();
        Storage::disk('public')->assertExists($product->image_url);
    }
}
