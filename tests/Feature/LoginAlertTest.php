<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Notifications\LoginAlertNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoginAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_dispatches_alert_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);

        Notification::assertSentTo($user, LoginAlertNotification::class, function ($notification) {
            return $notification->ip !== null && $notification->device !== null && $notification->browser !== null;
        });
    }

    public function test_disabled_user_does_not_get_login_alert(): void
    {
        Notification::fake();

        $user = User::factory()->create(['disabled_at' => now()]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        Notification::assertNotSentTo($user, LoginAlertNotification::class);
    }

    public function test_login_alerts_can_be_disabled_via_setting(): void
    {
        Notification::fake();
        Setting::set('login_alerts_enabled', '0');

        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($user);
        Notification::assertNotSentTo($user, LoginAlertNotification::class);
    }
}
