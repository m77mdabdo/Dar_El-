<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProduct(): Product
    {
        $category = Category::create([
            'name_ar' => 'عبايات', 'name_en' => 'Abayas', 'slug' => 'abayas', 'is_active' => true, 'sort_order' => 1,
        ]);

        return Product::create([
            'category_id' => $category->id,
            'name_ar' => 'عباية', 'name_en' => 'Abaya', 'slug' => 'abaya-'.uniqid(),
            'price' => 1000, 'is_active' => true, 'is_featured' => false,
        ]);
    }

    public function test_guest_cannot_add_to_wishlist_and_is_told_to_login(): void
    {
        $product = $this->makeProduct();

        // This app renders auth failures as a redirect to login everywhere
        // except api/* (see bootstrap/app.php shouldRenderJsonWhen), so a
        // browser's fetch() would follow this to the login page — handled
        // client-side by app.js's redirect-detection in djFetch().
        $response = $this->post(route('wishlist.add', $product));

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('wishlists', 0);
    }

    public function test_authenticated_user_can_add_and_remove_wishlist_item(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $addResponse = $this->actingAs($user)->postJson(route('wishlist.add', $product));
        $addResponse->assertOk()->assertJson(['in_wishlist' => true, 'count' => 1]);
        $this->assertDatabaseHas('wishlists', ['user_id' => $user->id, 'product_id' => $product->id]);

        $removeResponse = $this->actingAs($user)->deleteJson(route('wishlist.remove', $product));
        $removeResponse->assertOk()->assertJson(['in_wishlist' => false, 'count' => 0]);
        $this->assertDatabaseMissing('wishlists', ['user_id' => $user->id, 'product_id' => $product->id]);
    }

    public function test_adding_the_same_product_twice_does_not_create_a_duplicate(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();

        $this->actingAs($user)->postJson(route('wishlist.add', $product));
        $this->actingAs($user)->postJson(route('wishlist.add', $product));

        $this->assertSame(1, Wishlist::where('user_id', $user->id)->where('product_id', $product->id)->count());
    }

    public function test_wishlist_page_lists_saved_products(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $user->wishlists()->create(['product_id' => $product->id]);

        $response = $this->actingAs($user)->get(route('wishlist.index'));

        $response->assertOk();
        $response->assertSee($product->name_en);
    }

    public function test_move_to_cart_removes_wishlist_item_and_adds_to_cart(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $product->sizes()->create(['size' => 'M', 'stock' => 5]);
        $user->wishlists()->create(['product_id' => $product->id]);

        $response = $this->actingAs($user)->postJson(route('wishlist.move', $product), ['size' => 'M']);

        $response->assertOk()->assertJson(['cart_count' => 1, 'wishlist_count' => 0]);
        $this->assertDatabaseMissing('wishlists', ['user_id' => $user->id, 'product_id' => $product->id]);
    }

    public function test_move_to_cart_fails_when_size_is_out_of_stock(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $product->sizes()->create(['size' => 'M', 'stock' => 0]);
        $user->wishlists()->create(['product_id' => $product->id]);

        $response = $this->actingAs($user)->postJson(route('wishlist.move', $product), ['size' => 'M']);

        $response->assertStatus(422);
        $this->assertDatabaseHas('wishlists', ['user_id' => $user->id, 'product_id' => $product->id]);
    }
}
