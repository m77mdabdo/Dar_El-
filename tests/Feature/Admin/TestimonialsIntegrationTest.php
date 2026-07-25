<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * "Testimonials" turned out to already be a real, built mechanism —
 * Review::is_featured + ReviewController::feature()/unfeature() — just
 * missing an easy way to see "which reviews are featured" in the admin
 * Reviews screen, and missing any real wiring on the homepage (the
 * dj-testimonials section was three fully hardcoded fake reviews).
 * These tests cover the two things that were actually built: the
 * Featured filter/stat card, and the homepage integration (with the
 * same opt-in/no-visible-change-when-empty rule as Hero Banners).
 */
class TestimonialsIntegrationTest extends TestCase
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
        $category = Category::create([
            'name_ar' => 'فئة', 'name_en' => 'Category', 'slug' => 'cat-'.uniqid(),
            'is_active' => true, 'sort_order' => 1,
        ]);

        return Product::create([
            'category_id' => $category->id, 'name_ar' => 'منتج', 'name_en' => 'Product',
            'slug' => 'product-'.uniqid(), 'price' => 200, 'is_active' => true, 'is_featured' => false,
        ]);
    }

    protected function makeReview(array $overrides = []): Review
    {
        return Review::create(array_merge([
            'product_id' => $this->makeProduct()->id,
            'name' => 'Test Customer',
            'rating' => 5,
            'comment' => 'A perfectly ordinary review comment of sufficient length.',
            'status' => 'approved',
            'is_featured' => false,
        ], $overrides));
    }

    // ---------------------------------------------------------------
    // Admin: Featured filter / stat card
    // ---------------------------------------------------------------

    public function test_featured_filter_shows_only_featured_reviews(): void
    {
        $admin = $this->makeAdmin();
        $this->makeReview(['name' => 'Featured One', 'is_featured' => true]);
        $this->makeReview(['name' => 'Not Featured', 'is_featured' => false]);

        $response = $this->actingAs($admin)->get(route('admin.reviews.index', ['featured' => 1]));

        $response->assertOk();
        $response->assertSee('Featured One');
        $response->assertDontSee('Not Featured');
    }

    public function test_featured_stat_card_reflects_the_correct_count(): void
    {
        $admin = $this->makeAdmin();
        $this->makeReview(['is_featured' => true]);
        $this->makeReview(['is_featured' => true]);
        $this->makeReview(['is_featured' => false]);

        $response = $this->actingAs($admin)->get(route('admin.reviews.index'));

        $response->assertOk();
        $response->assertSee(__('reviews.stat_featured'));
        // The stat card renders the count as a plain number in the page.
        $response->assertSeeInOrder([__('reviews.stat_featured'), '2']);
    }

    // ---------------------------------------------------------------
    // Homepage integration
    // ---------------------------------------------------------------

    public function test_homepage_shows_default_hardcoded_testimonials_when_none_are_featured(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Sara A.');
    }

    public function test_homepage_shows_a_real_featured_and_approved_review_instead_of_the_default(): void
    {
        $this->makeReview([
            'name' => 'Amazing Real Customer', 'comment' => 'This is a genuinely wonderful real testimonial from a customer.',
            'rating' => 4, 'status' => 'approved', 'is_featured' => true,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Amazing Real Customer');
        $response->assertSee('This is a genuinely wonderful real testimonial from a customer.');
        $response->assertDontSee('Sara A.');
    }

    public function test_homepage_ignores_a_featured_review_that_is_not_approved(): void
    {
        // A review can be flagged featured regardless of status (the admin
        // feature/unfeature button isn't gated on approval) — the
        // homepage query itself is the safety net that keeps a pending or
        // rejected review from ever appearing as a testimonial.
        $this->makeReview(['name' => 'Pending Not Live', 'status' => 'pending', 'is_featured' => true]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('Pending Not Live');
        $response->assertSee('Sara A.');
    }

    public function test_homepage_ignores_an_approved_review_that_is_not_featured(): void
    {
        $this->makeReview(['name' => 'Approved But Not Featured', 'status' => 'approved', 'is_featured' => false]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee('Approved But Not Featured');
        $response->assertSee('Sara A.');
    }

    public function test_featuring_a_review_via_admin_immediately_changes_the_live_homepage(): void
    {
        Cache::flush();
        $admin = $this->makeAdmin();
        $review = $this->makeReview(['name' => 'Instant Testimonial', 'status' => 'approved', 'is_featured' => false]);

        // Prime the "no featured reviews" cached state first, same as a
        // real visitor hitting the homepage before an admin ever features
        // anything — proves Review::booted()'s cache-busting actually
        // fires, not just that a fresh/uncached request happens to work.
        $this->get('/')->assertSee('Sara A.');

        $this->actingAs($admin)->patch(route('admin.reviews.feature', $review));

        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('Instant Testimonial');
    }
}
