<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RelatedProductsTest extends TestCase
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

    protected function makeProduct(string $name, ?Category $category = null, bool $active = true, int $stock = 5): Product
    {
        $product = Product::create([
            'category_id' => ($category ?? $this->defaultCategory())->id,
            'name_ar' => $name, 'name_en' => $name, 'slug' => Str::slug($name).'-'.uniqid(),
            'price' => 500, 'is_active' => $active, 'is_featured' => false,
        ]);

        if ($stock > 0) {
            $product->sizes()->create(['size' => 'M', 'stock' => $stock]);
        }

        return $product;
    }

    protected function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    // ---------------------------------------------------------------
    // Display / fallback logic
    // ---------------------------------------------------------------

    public function test_manually_curated_relations_display_and_take_priority(): void
    {
        $main = $this->makeProduct('Main Abaya');
        $belt = $this->makeProduct('Curated Belt');
        // Same category, would otherwise be picked up automatically — must
        // NOT appear once manual picks already fill every slot.
        $automatic1 = $this->makeProduct('Automatic Candidate 1');
        $automatic2 = $this->makeProduct('Automatic Candidate 2');
        $automatic3 = $this->makeProduct('Automatic Candidate 3');
        $main->relatedProducts()->sync([$belt->id]);

        $response = $this->get(route('shop.show', $main));

        $response->assertOk();
        $response->assertSee('Curated Belt');
    }

    public function test_automatic_fallback_fills_remaining_slots_from_same_category(): void
    {
        $main = $this->makeProduct('Main Abaya');
        $belt = $this->makeProduct('Curated Belt');
        $main->relatedProducts()->sync([$belt->id]);

        $sameCategoryA = $this->makeProduct('Same Category A');
        $sameCategoryB = $this->makeProduct('Same Category B');
        $otherCategory = $this->makeCategory('Other Category');
        $wrongCategory = $this->makeProduct('Wrong Category Product', $otherCategory);

        $response = $this->get(route('shop.show', $main));

        $response->assertOk();
        $response->assertSee('Curated Belt');
        $response->assertSee('Same Category A');
        $response->assertSee('Same Category B');
        $response->assertDontSee('Wrong Category Product');
    }

    public function test_automatic_fallback_excludes_out_of_stock_products(): void
    {
        $main = $this->makeProduct('Main Abaya');
        $inStock = $this->makeProduct('In Stock Candidate', stock: 5);
        $outOfStock = $this->makeProduct('Out Of Stock Candidate', stock: 0);

        $response = $this->get(route('shop.show', $main));

        $response->assertOk();
        $response->assertSee('In Stock Candidate');
        $response->assertDontSee('Out Of Stock Candidate');
    }

    public function test_the_product_never_appears_in_its_own_suggestions(): void
    {
        $main = $this->makeProduct('Main Abaya');
        $this->makeProduct('Other Product');

        // Authenticated so the page's own wishlist button (@auth-gated,
        // unlike product-card.blade.php's) renders and gives us a reliable
        // once-per-product marker to count.
        $response = $this->actingAs(User::factory()->create())->get(route('shop.show', $main));

        $response->assertOk();
        // The product's own detail page renders exactly one
        // data-wishlist-product marker for itself (its own wishlist button).
        // A second occurrence would mean it also rendered as one of its own
        // suggestion cards, since product-card.blade.php emits the same
        // attribute once per card.
        $count = substr_count($response->getContent(), 'data-wishlist-product="'.$main->id.'"');
        $this->assertSame(1, $count, 'The product appeared in its own related-products suggestions.');
    }

    public function test_manual_and_automatic_picks_never_duplicate(): void
    {
        $main = $this->makeProduct('Main Abaya');
        $manualPick = $this->makeProduct('Manual Pick');
        $main->relatedProducts()->sync([$manualPick->id]);

        // Enough same-category candidates to fill every remaining slot,
        // including one that (if the exclusion were missing) could collide
        // with the manual pick's own id.
        $this->makeProduct('Filler A');
        $this->makeProduct('Filler B');
        $this->makeProduct('Filler C');

        $response = $this->get(route('shop.show', $main));

        $response->assertOk();
        // Exactly one product-card render for the manual pick — a second
        // data-wishlist-product marker for the same id would mean it was
        // also pulled in by the automatic fallback query.
        $this->assertSame(1, substr_count($response->getContent(), 'data-wishlist-product="'.$manualPick->id.'"'));
    }

    public function test_fewer_than_display_count_manual_relations_still_fill_up_with_automatic(): void
    {
        $main = $this->makeProduct('Main Abaya');
        $manualPick = $this->makeProduct('Manual Pick');
        $main->relatedProducts()->sync([$manualPick->id]);

        $filler1 = $this->makeProduct('Filler One');
        $filler2 = $this->makeProduct('Filler Two');
        $filler3 = $this->makeProduct('Filler Three');

        $response = $this->get(route('shop.show', $main));

        $response->assertOk();
        $response->assertSee('Manual Pick');
        $response->assertSee('Filler One');
        $response->assertSee('Filler Two');
        $response->assertSee('Filler Three');
    }

    protected function makeCategory(string $name): Category
    {
        return Category::create([
            'name_ar' => $name, 'name_en' => $name, 'slug' => Str::slug($name).'-'.uniqid(),
            'is_active' => true, 'sort_order' => 1,
        ]);
    }

    // ---------------------------------------------------------------
    // Admin
    // ---------------------------------------------------------------

    public function test_admin_can_set_related_products_on_create(): void
    {
        $admin = $this->admin();
        $category = $this->defaultCategory();
        $existingA = $this->makeProduct('Existing A');
        $existingB = $this->makeProduct('Existing B');

        $response = $this->actingAs($admin)->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name_ar' => 'منتج جديد', 'name_en' => 'New Product',
            'price' => 400, 'status' => 'published',
            'related_product_ids' => [$existingA->id, $existingB->id],
        ]);

        $response->assertSessionHasNoErrors();

        $newProduct = Product::where('name_en', 'New Product')->firstOrFail();
        $this->assertEqualsCanonicalizing(
            [$existingA->id, $existingB->id],
            $newProduct->relatedProducts()->pluck('products.id')->all()
        );
    }

    public function test_admin_can_update_related_products_and_sync_clears_old_ones(): void
    {
        $admin = $this->admin();
        $product = $this->makeProduct('Editable Product');
        $oldPick = $this->makeProduct('Old Pick');
        $newPick = $this->makeProduct('New Pick');
        $product->relatedProducts()->sync([$oldPick->id]);

        $response = $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $product->category_id,
            'name_ar' => $product->name_ar, 'name_en' => $product->name_en,
            'price' => $product->price, 'status' => 'published',
            'related_product_ids' => [$newPick->id],
        ]);

        $response->assertSessionHasNoErrors();

        $ids = $product->relatedProducts()->pluck('products.id')->all();
        $this->assertSame([$newPick->id], $ids);
    }

    public function test_admin_can_clear_all_related_products(): void
    {
        $admin = $this->admin();
        $product = $this->makeProduct('Editable Product');
        $pick = $this->makeProduct('Pick');
        $product->relatedProducts()->sync([$pick->id]);

        $response = $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $product->category_id,
            'name_ar' => $product->name_ar, 'name_en' => $product->name_en,
            'price' => $product->price, 'status' => 'published',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertSame(0, $product->relatedProducts()->count());
    }

    public function test_admin_product_form_renders_the_related_products_picker_with_correct_selection(): void
    {
        $admin = $this->admin();
        $product = $this->makeProduct('Editable Product');
        $pick = $this->makeProduct('Selected Candidate');
        $unselected = $this->makeProduct('Unselected Candidate');
        $product->relatedProducts()->sync([$pick->id]);

        $response = $this->actingAs($admin)->get(route('admin.products.edit', $product));

        $response->assertOk();
        $response->assertSee('name="related_product_ids[]"', false);
        $response->assertSee('<option value="'.$pick->id.'" data-search="selected candidate selected candidate" selected', false);
        $response->assertSee('<option value="'.$unselected->id.'" data-search="unselected candidate unselected candidate" >', false);
    }

    public function test_invalid_related_product_id_is_rejected(): void
    {
        $admin = $this->admin();
        $product = $this->makeProduct('Editable Product');

        $response = $this->actingAs($admin)->put(route('admin.products.update', $product), [
            'category_id' => $product->category_id,
            'name_ar' => $product->name_ar, 'name_en' => $product->name_en,
            'price' => $product->price, 'status' => 'published',
            'related_product_ids' => [999999],
        ]);

        $response->assertSessionHasErrors('related_product_ids.0');
    }
}
