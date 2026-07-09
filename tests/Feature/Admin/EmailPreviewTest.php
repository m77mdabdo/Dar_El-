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
}
