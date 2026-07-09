<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Notifications\NewProductReviewSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function makeProduct(): Product
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-'.uniqid(),
            'is_active' => true, 'sort_order' => 1,
        ]);

        return Product::create([
            'category_id' => $category->id, 'name_ar' => 'منتج', 'name_en' => 'Product',
            'slug' => 'product-'.uniqid(), 'price' => 200, 'is_active' => true, 'is_featured' => false,
        ]);
    }

    protected function makeAdmin(): User
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    public function test_guest_cannot_submit_a_review(): void
    {
        $product = $this->makeProduct();

        $response = $this->post(route('reviews.store', $product), ['rating' => 5, 'comment' => 'Really great product!']);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_authenticated_user_can_submit_a_pending_review(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('reviews.store', $product), [
            'rating' => 4,
            'comment' => 'A genuinely lovely piece, fits perfectly.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reviews', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'rating' => 4,
            'status' => 'pending',
            'is_verified_purchase' => false,
        ]);

        Notification::assertSentTo($admin, NewProductReviewSubmitted::class);
    }

    public function test_review_is_marked_verified_when_user_has_a_delivered_order_for_the_product(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();

        $order = Order::create([
            'user_id' => $user->id, 'order_number' => 'ORD-'.uniqid(),
            'customer_name' => $user->name, 'customer_email' => $user->email, 'customer_phone' => '010',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => 'x',
            'subtotal' => 200, 'shipping_fee' => 0, 'total' => 200, 'status' => 'delivered', 'payment_method' => 'cod',
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name_en, 'size' => null, 'price' => 200, 'quantity' => 1]);

        $this->actingAs($user)->post(route('reviews.store', $product), [
            'rating' => 5, 'comment' => 'Exactly as pictured, wonderful quality.',
        ]);

        $this->assertDatabaseHas('reviews', ['product_id' => $product->id, 'user_id' => $user->id, 'is_verified_purchase' => true]);
    }

    public function test_review_is_unverified_when_order_is_not_delivered(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();

        $order = Order::create([
            'user_id' => $user->id, 'order_number' => 'ORD-'.uniqid(),
            'customer_name' => $user->name, 'customer_email' => $user->email, 'customer_phone' => '010',
            'governorate' => 'Cairo', 'city' => 'Nasr City', 'address' => 'x',
            'subtotal' => 200, 'shipping_fee' => 0, 'total' => 200, 'status' => 'processing', 'payment_method' => 'cod',
        ]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'product_name' => $product->name_en, 'size' => null, 'price' => 200, 'quantity' => 1]);

        $this->actingAs($user)->post(route('reviews.store', $product), [
            'rating' => 5, 'comment' => 'Exactly as pictured, wonderful quality.',
        ]);

        $this->assertDatabaseHas('reviews', ['product_id' => $product->id, 'user_id' => $user->id, 'is_verified_purchase' => false]);
    }

    public function test_duplicate_review_is_rejected(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();

        Review::create(['product_id' => $product->id, 'user_id' => $user->id, 'name' => $user->name, 'rating' => 4, 'comment' => 'Already reviewed this before.', 'status' => 'pending']);

        $response = $this->actingAs($user)->post(route('reviews.store', $product), [
            'rating' => 5, 'comment' => 'Trying to review again, should fail.',
        ]);

        $response->assertSessionHasErrors('rating');
        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_rating_and_comment_validation(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('reviews.store', $product), ['rating' => 6, 'comment' => 'Valid length comment here.'])
            ->assertSessionHasErrors('rating');

        $this->actingAs($user)->post(route('reviews.store', $product), ['rating' => 3, 'comment' => 'short'])
            ->assertSessionHasErrors('comment');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_owner_can_edit_a_pending_review(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();

        $review = Review::create(['product_id' => $product->id, 'user_id' => $user->id, 'name' => $user->name, 'rating' => 3, 'comment' => 'Initial comment text here.', 'status' => 'pending']);

        $response = $this->actingAs($user)->patch(route('reviews.update', $review), [
            'rating' => 5, 'comment' => 'Updated comment, much better now.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 5]);
    }

    public function test_owner_cannot_edit_an_approved_review(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();

        $review = Review::create(['product_id' => $product->id, 'user_id' => $user->id, 'name' => $user->name, 'rating' => 3, 'comment' => 'Initial comment text here.', 'status' => 'approved']);

        $response = $this->actingAs($user)->patch(route('reviews.update', $review), [
            'rating' => 5, 'comment' => 'Trying to edit an approved review.',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 3]);
    }

    public function test_owner_can_delete_review_regardless_of_status(): void
    {
        $product = $this->makeProduct();
        $user = User::factory()->create();

        $review = Review::create(['product_id' => $product->id, 'user_id' => $user->id, 'name' => $user->name, 'rating' => 3, 'comment' => 'Initial comment text here.', 'status' => 'approved']);

        $this->actingAs($user)->delete(route('reviews.destroy', $review))->assertRedirect();

        $this->assertSoftDeleted('reviews', ['id' => $review->id]);
    }

    public function test_another_user_cannot_update_or_delete_someone_elses_review(): void
    {
        $product = $this->makeProduct();
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $review = Review::create(['product_id' => $product->id, 'user_id' => $owner->id, 'name' => $owner->name, 'rating' => 3, 'comment' => 'Owner comment text here.', 'status' => 'pending']);

        $this->actingAs($intruder)->patch(route('reviews.update', $review), ['rating' => 1, 'comment' => 'Malicious edit attempt.'])->assertForbidden();
        $this->actingAs($intruder)->delete(route('reviews.destroy', $review))->assertForbidden();

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'rating' => 3, 'deleted_at' => null]);
    }

    public function test_only_approved_reviews_appear_on_the_product_page(): void
    {
        $product = $this->makeProduct();

        Review::create(['product_id' => $product->id, 'name' => 'Approved Reviewer', 'rating' => 5, 'comment' => 'This is an approved review comment.', 'status' => 'approved']);
        Review::create(['product_id' => $product->id, 'name' => 'Pending Reviewer', 'rating' => 4, 'comment' => 'This is a pending review comment.', 'status' => 'pending']);
        Review::create(['product_id' => $product->id, 'name' => 'Rejected Reviewer', 'rating' => 2, 'comment' => 'This is a rejected review comment.', 'status' => 'rejected']);

        $response = $this->get(route('shop.show', $product));

        $response->assertSee('Approved Reviewer');
        $response->assertDontSee('Pending Reviewer');
        $response->assertDontSee('Rejected Reviewer');
    }

    public function test_helpful_count_increments_via_public_route(): void
    {
        $product = $this->makeProduct();
        $review = Review::create(['product_id' => $product->id, 'name' => 'Someone', 'rating' => 5, 'comment' => 'A genuinely helpful review comment.', 'status' => 'approved']);

        $this->post(route('reviews.helpful', $review))->assertRedirect();

        $this->assertDatabaseHas('reviews', ['id' => $review->id, 'helpful_count' => 1]);
    }
}
