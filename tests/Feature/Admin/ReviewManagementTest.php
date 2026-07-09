<?php

namespace Tests\Feature\Admin;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Notifications\ReviewStatusUpdated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReviewManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        return $admin;
    }

    protected function makeProduct(string $nameEn = 'Product'): Product
    {
        $category = Category::create([
            'name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-'.uniqid(),
            'is_active' => true, 'sort_order' => 1,
        ]);

        return Product::create([
            'category_id' => $category->id, 'name_ar' => 'منتج', 'name_en' => $nameEn,
            'slug' => 'product-'.uniqid(), 'price' => 200, 'is_active' => true, 'is_featured' => false,
        ]);
    }

    public function test_non_admin_is_forbidden_from_admin_review_routes(): void
    {
        $customer = User::factory()->create();
        $customer->assignRole(Role::findOrCreate('customer', 'web'));
        $review = Review::create(['product_id' => $this->makeProduct()->id, 'name' => 'X', 'rating' => 3, 'comment' => 'A comment of sufficient length.', 'status' => 'pending']);

        $this->actingAs($customer)->get(route('admin.reviews.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.reviews.show', $review))->assertForbidden();
        $this->actingAs($customer)->patch(route('admin.reviews.approve', $review))->assertForbidden();
    }

    public function test_admin_can_approve_a_pending_review(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $review = Review::create(['product_id' => $product->id, 'user_id' => $user->id, 'name' => $user->name, 'rating' => 4, 'comment' => 'A comment of sufficient length.', 'status' => 'pending']);

        $this->actingAs($admin)->patch(route('admin.reviews.approve', $review))->assertRedirect();

        $review->refresh();
        $this->assertSame('approved', $review->status);
        $this->assertNotNull($review->approved_at);
        $this->assertSame($admin->id, $review->approved_by);

        Notification::assertSentTo($user, ReviewStatusUpdated::class);
        $this->assertDatabaseHas('activity_logs', ['action' => 'approved', 'subject_type' => Review::class, 'subject_id' => $review->id]);
    }

    public function test_admin_can_reject_with_a_reason(): void
    {
        Notification::fake();
        $admin = $this->makeAdmin();
        $user = User::factory()->create();
        $product = $this->makeProduct();
        $review = Review::create(['product_id' => $product->id, 'user_id' => $user->id, 'name' => $user->name, 'rating' => 1, 'comment' => 'A comment of sufficient length.', 'status' => 'pending']);

        $this->actingAs($admin)->patch(route('admin.reviews.reject', $review), ['reason' => 'Contains inappropriate language.'])->assertRedirect();

        $review->refresh();
        $this->assertSame('rejected', $review->status);
        $this->assertSame('Contains inappropriate language.', $review->rejection_reason);
        $this->assertSame($admin->id, $review->rejected_by);

        Notification::assertSentTo($user, ReviewStatusUpdated::class);
    }

    public function test_admin_can_feature_and_unfeature_a_review(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();
        $review = Review::create(['product_id' => $product->id, 'name' => 'X', 'rating' => 5, 'comment' => 'A comment of sufficient length.', 'status' => 'approved']);

        $this->actingAs($admin)->patch(route('admin.reviews.feature', $review))->assertRedirect();
        $this->assertTrue($review->fresh()->is_featured);

        $this->actingAs($admin)->patch(route('admin.reviews.unfeature', $review))->assertRedirect();
        $this->assertFalse($review->fresh()->is_featured);
    }

    public function test_admin_can_soft_delete_a_review(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();
        $review = Review::create(['product_id' => $product->id, 'name' => 'X', 'rating' => 5, 'comment' => 'A comment of sufficient length.', 'status' => 'approved']);

        $this->actingAs($admin)->delete(route('admin.reviews.destroy', $review))->assertRedirect(route('admin.reviews.index'));

        $this->assertSoftDeleted('reviews', ['id' => $review->id]);
        $this->actingAs($admin)->get(route('admin.reviews.index'))->assertDontSee('A comment of sufficient length.');
    }

    public function test_index_filters_narrow_results_correctly(): void
    {
        $admin = $this->makeAdmin();
        $productA = $this->makeProduct('Alpha Dress');
        $productB = $this->makeProduct('Beta Kaftan');

        $pending = Review::create(['product_id' => $productA->id, 'name' => 'Pending Person', 'rating' => 3, 'comment' => 'This one is still pending review.', 'status' => 'pending']);
        $approved = Review::create(['product_id' => $productB->id, 'name' => 'Approved Person', 'rating' => 5, 'comment' => 'This one has been approved already.', 'status' => 'approved', 'is_verified_purchase' => true]);

        $this->actingAs($admin);

        $response = $this->get(route('admin.reviews.index', ['status' => 'pending']));
        $response->assertSee('Pending Person')->assertDontSee('Approved Person');

        $response = $this->get(route('admin.reviews.index', ['status' => 'approved']));
        $response->assertSee('Approved Person')->assertDontSee('Pending Person');

        $response = $this->get(route('admin.reviews.index', ['rating' => 5]));
        $response->assertSee('Approved Person')->assertDontSee('Pending Person');

        $response = $this->get(route('admin.reviews.index', ['product_id' => $productA->id]));
        $response->assertSee('Pending Person')->assertDontSee('Approved Person');

        $response = $this->get(route('admin.reviews.index', ['verified' => '1']));
        $response->assertSee('Approved Person')->assertDontSee('Pending Person');

        $response = $this->get(route('admin.reviews.index', ['search' => 'Beta Kaftan']));
        $response->assertSee('Approved Person')->assertDontSee('Pending Person');

        unset($pending, $approved);
    }

    public function test_show_page_renders_photos_and_rejection_reason(): void
    {
        $admin = $this->makeAdmin();
        $product = $this->makeProduct();
        $review = Review::create([
            'product_id' => $product->id, 'name' => 'X', 'rating' => 2,
            'comment' => 'A comment of sufficient length.', 'status' => 'rejected',
            'rejection_reason' => 'Not related to the product.',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reviews.show', $review));

        $response->assertOk();
        $response->assertSee('Not related to the product.');
    }
}
