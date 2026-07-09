<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\LoginAlertNotification;
use App\Notifications\NewCustomerRegistered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Notification;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function mockGoogleUser(array $attributes): void
    {
        $socialiteUser = (new SocialiteUser())->map(array_merge([
            'id' => '1234567890',
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'avatar' => 'https://lh3.googleusercontent.com/a/jane.jpg',
        ], $attributes));

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_redirect_sends_the_user_to_google_for_an_enabled_provider(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('redirect')->andReturn(new RedirectResponse('https://accounts.google.com/o/oauth2/auth'));

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $response = $this->get(route('social.redirect', 'google'));

        $response->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }

    public function test_redirect_404s_for_a_provider_that_is_not_enabled(): void
    {
        $this->get(route('social.redirect', 'facebook'))->assertNotFound();
    }

    public function test_callback_creates_a_new_verified_customer_and_logs_them_in(): void
    {
        Notification::fake();
        $admin = User::factory()->create();
        $admin->assignRole(\Spatie\Permission\Models\Role::findOrCreate('admin', 'web'));
        $this->mockGoogleUser([]);

        $response = $this->get(route('social.callback', 'google'));

        $user = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('google', $user->provider);
        $this->assertSame('1234567890', $user->provider_id);
        $this->assertSame('https://lh3.googleusercontent.com/a/jane.jpg', $user->avatar);
        $this->assertTrue($user->hasRole('customer'));
        $response->assertRedirect(route('home'));
        $response->assertSessionHas('status', __('Your account has been created successfully.'));

        Notification::assertSentTo($user, LoginAlertNotification::class, fn ($n) => $n->provider === 'google');
        Notification::assertSentTo(User::admins(), NewCustomerRegistered::class);
    }

    public function test_callback_logs_in_an_existing_user_matched_by_provider_id(): void
    {
        $existing = User::factory()->create();
        $existing->assignRole(\Spatie\Permission\Models\Role::findOrCreate('customer', 'web'));
        $existing->update(['provider' => 'google', 'provider_id' => '1234567890', 'email' => 'jane@example.com']);

        $this->mockGoogleUser([]);

        $this->get(route('social.callback', 'google'));

        $this->assertAuthenticatedAs($existing);
        $this->assertSame(1, User::count());
    }

    public function test_callback_links_provider_to_an_existing_account_with_the_same_email_without_duplicating(): void
    {
        $existing = User::factory()->create(['email' => 'jane@example.com', 'email_verified_at' => null]);
        $existing->assignRole(\Spatie\Permission\Models\Role::findOrCreate('customer', 'web'));

        $this->mockGoogleUser([]);

        $this->get(route('social.callback', 'google'));

        $this->assertSame(1, User::count());
        $existing->refresh();
        $this->assertAuthenticatedAs($existing);
        $this->assertSame('google', $existing->provider);
        $this->assertSame('1234567890', $existing->provider_id);
        $this->assertNotNull($existing->email_verified_at);
    }

    public function test_callback_rejects_a_disabled_account(): void
    {
        $existing = User::factory()->create(['email' => 'jane@example.com', 'disabled_at' => now()]);
        $existing->assignRole(\Spatie\Permission\Models\Role::findOrCreate('customer', 'web'));

        $this->mockGoogleUser([]);

        $response = $this->get(route('social.callback', 'google'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
    }

    public function test_callback_shows_a_friendly_error_when_the_provider_gives_no_email(): void
    {
        $this->mockGoogleUser(['email' => null]);

        $response = $this->get(route('social.callback', 'google'));

        $this->assertGuest();
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
    }

    public function test_admin_redirects_to_the_admin_dashboard_after_social_login(): void
    {
        $admin = User::factory()->create(['email' => 'jane@example.com']);
        $admin->assignRole(\Spatie\Permission\Models\Role::findOrCreate('admin', 'web'));

        $this->mockGoogleUser([]);

        $response = $this->get(route('social.callback', 'google'));

        $response->assertRedirect(route('admin.dashboard'));
    }
}
