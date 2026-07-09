<?php
namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\OtpVerificationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthRedirectAdminEscapeTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_never_lands_in_admin_after_stashed_admin_intended_url_via_registration(): void
    {
        Notification::fake();

        // Guest bounces off an admin-only route first (Laravel's auth
        // middleware stashes it as the "intended" post-login destination).
        $this->get('/admin/dashboard')->assertRedirect(route('login'));
        $this->assertSame(url('/admin/dashboard'), session('url.intended'));

        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'repro@example.com',
            'phone' => '01000000000',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('otp.notice'));

        $user = User::where('email', 'repro@example.com')->first();

        $otp = null;
        Notification::assertSentTo($user, OtpVerificationNotification::class, function ($notification) use (&$otp) {
            $otp = $notification->otp;

            return true;
        });

        $resp = $this->post('/verify-otp', ['otp' => $otp]);

        // Must land on the public homepage, never inside /admin.
        $resp->assertRedirect(route('home'));
        $this->assertNull(session('url.intended'));
    }

    public function test_admin_intended_url_is_honored_for_admin_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $user->markEmailAsVerified();

        session(['url.intended' => url('/admin/orders')]);

        $resp = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $resp->assertRedirect(url('/admin/orders'));
    }

    public function test_customer_intended_checkout_url_is_preserved_through_login(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);
        $user->assignRole(Role::findOrCreate('customer', 'web'));
        $user->markEmailAsVerified();

        session(['url.intended' => url('/checkout')]);

        $resp = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $resp->assertRedirect(url('/checkout'));
    }

    public function test_customer_login_with_no_intended_url_goes_home(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);
        $user->assignRole(Role::findOrCreate('customer', 'web'));
        $user->markEmailAsVerified();

        $resp = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $resp->assertRedirect(route('home'));
    }

    public function test_admin_login_with_no_intended_url_goes_to_admin_dashboard(): void
    {
        $user = User::factory()->create(['password' => bcrypt('password123')]);
        $user->assignRole(Role::findOrCreate('admin', 'web'));
        $user->markEmailAsVerified();

        $resp = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $resp->assertRedirect(route('admin.dashboard'));
    }

    public function test_already_authenticated_admin_visiting_login_page_is_sent_to_admin_dashboard(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('admin', 'web'));

        $this->actingAs($user)->get('/login')->assertRedirect(route('admin.dashboard'));
    }

    public function test_already_authenticated_customer_visiting_register_page_is_sent_home(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('customer', 'web'));

        $this->actingAs($user)->get('/register')->assertRedirect(route('home'));
    }
}
