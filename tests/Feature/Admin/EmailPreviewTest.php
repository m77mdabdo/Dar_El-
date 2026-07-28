<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmailPreviewTest extends TestCase
{
    use RefreshDatabase;

    protected const TYPES = [
        'otp', 'login-alert', 'order-confirmation', 'order-status-updated',
        'cart-abandoned-reminder', 'review-approved', 'review-rejected',
        'blog-comment-approved', 'blog-comment-rejected', 'admin-new-order',
        'admin-new-review', 'admin-new-blog-comment', 'admin-low-stock', 'admin-out-of-stock',
        'admin-new-customer', 'admin-new-contact-message', 'payment-success',
        'payment-failed', 'wishlist-reminder', 'back-in-stock', 'newsletter-welcome',
    ];

    public function test_all_preview_types_render_in_both_locales(): void
    {
        $this->app['env'] = 'local';

        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        foreach (['en', 'ar'] as $locale) {
            session(['locale' => $locale]);

            foreach (self::TYPES as $type) {
                $response = $this->actingAs($admin)->get(route('admin.email-preview.show', $type));
                $response->assertOk();
            }
        }
    }

    public function test_preview_is_blocked_outside_local_environment(): void
    {
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)->get(route('admin.email-preview.show', 'otp'))->assertNotFound();
    }

    /**
     * The index page (linked from the sidebar's "Emails" entry) lists
     * every template this controller supports — genuinely new; the tool
     * itself already existed but had no discoverable list, only the bare
     * show($type) action.
     */
    public function test_index_lists_every_supported_template_in_local_environment(): void
    {
        $this->app['env'] = 'local';

        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        $response = $this->actingAs($admin)->get(route('admin.email-preview.index'));

        $response->assertOk();
        foreach (self::TYPES as $type) {
            $response->assertSee(route('admin.email-preview.show', $type), false);
        }
    }

    public function test_index_shows_a_not_available_message_outside_local_environment_instead_of_a_404(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        $response = $this->actingAs($admin)->get(route('admin.email-preview.index'));

        // Deliberately NOT a 404 — this is the page reached from the
        // sidebar first, so it explains itself rather than looking broken.
        $response->assertOk();
        $response->assertSee(__('email_preview.not_local_title'));
        $response->assertDontSee(route('admin.email-preview.show', 'otp'), false);
    }

    public function test_guest_is_redirected_to_login_from_the_index(): void
    {
        $response = $this->get(route('admin.email-preview.index'));

        $response->assertRedirect(route('login'));
    }
}
