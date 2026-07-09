<?php

namespace Tests\Feature;

use App\Jobs\SendAbandonedCartReminderJob;
use App\Models\Cart;
use App\Models\CartReminder;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\AbandonedCartReminderNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CartReminderSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCart(User $user, array $overrides = []): Cart
    {
        return Cart::create(array_merge([
            'user_id' => $user->id,
            'status' => 'active',
            'subtotal' => 500,
            'total' => 500,
            'items_count' => 1,
            'last_activity_at' => now(),
        ], $overrides));
    }

    public function test_automatic_reminder_records_source_automatic(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $cart = $this->makeCart($user, ['status' => 'abandoned', 'last_activity_at' => now()->subHours(3)]);

        $this->artisan('carts:send-reminders')->assertSuccessful();

        $reminder = CartReminder::where('cart_id', $cart->id)->first();
        $this->assertNotNull($reminder);
        $this->assertSame('automatic', $reminder->source);
    }

    public function test_disabling_automatic_reminders_skips_scheduled_dispatch_but_still_transitions_carts(): void
    {
        Notification::fake();
        Setting::set('cart_reminders_enabled', '0');

        $user = User::factory()->create();
        $cart = $this->makeCart($user, ['last_activity_at' => now()->subHours(2)]);

        $this->artisan('carts:send-reminders')->assertSuccessful();

        $this->assertSame('abandoned', $cart->fresh()->status);
        Notification::assertNotSentTo($user, AbandonedCartReminderNotification::class);
        $this->assertSame(0, CartReminder::where('cart_id', $cart->id)->count());
    }

    public function test_disabling_automatic_reminders_does_not_block_the_manual_admin_button(): void
    {
        Notification::fake();
        Setting::set('cart_reminders_enabled', '0');

        $user = User::factory()->create();
        $cart = $this->makeCart($user, ['status' => 'abandoned', 'last_activity_at' => now()->subHours(3)]);

        SendAbandonedCartReminderJob::dispatchSync($cart, force: true);

        $this->assertSame(1, $cart->fresh()->reminder_count);
        $this->assertSame('manual', CartReminder::where('cart_id', $cart->id)->first()->source);
    }

    public function test_disabling_customer_notification_setting_sends_mail_only(): void
    {
        Notification::fake();
        Setting::set('cart_reminder_notification_enabled', '0');

        $user = User::factory()->create();
        $notification = new AbandonedCartReminderNotification($this->makeCart($user, ['status' => 'abandoned']));

        $this->assertSame(['mail'], $notification->via($user));
    }

    public function test_customer_notification_channel_included_by_default(): void
    {
        $user = User::factory()->create();
        $notification = new AbandonedCartReminderNotification($this->makeCart($user, ['status' => 'abandoned']));

        $this->assertSame(['mail', 'database'], $notification->via($user));
    }

    public function test_custom_reminder_interval_and_max_settings_are_respected(): void
    {
        Notification::fake();
        Setting::set('cart_reminder_interval_hours', '1');
        Setting::set('cart_max_reminders', '1');

        $user = User::factory()->create();
        // Would be ineligible under the default 4h interval, but eligible under the 1h override.
        $cart = $this->makeCart($user, [
            'status' => 'abandoned',
            'last_activity_at' => now()->subHours(5),
            'reminder_count' => 0,
            'last_reminder_sent_at' => now()->subHours(2),
        ]);

        $this->artisan('carts:send-reminders')->assertSuccessful();

        Notification::assertSentTo($user, AbandonedCartReminderNotification::class);
        $this->assertSame(1, $cart->fresh()->reminder_count);

        // Now at the (overridden) cap of 1 — a second run must not send again.
        $this->artisan('carts:send-reminders')->assertSuccessful();
        $this->assertSame(1, $cart->fresh()->reminder_count);
    }

    public function test_next_eligible_reminder_at_for_a_still_active_cart(): void
    {
        $user = User::factory()->create();
        $cart = $this->makeCart($user, ['status' => 'active', 'last_activity_at' => now()]);

        $next = $cart->nextEligibleReminderAt();

        $this->assertNotNull($next);
        $this->assertTrue($next->isFuture());
    }

    public function test_next_eligible_reminder_at_is_null_once_the_cap_is_reached(): void
    {
        $user = User::factory()->create();
        $cart = $this->makeCart($user, ['status' => 'abandoned', 'reminder_count' => 3, 'last_reminder_sent_at' => now()->subDay()]);

        $this->assertNull($cart->nextEligibleReminderAt());
    }

    public function test_next_eligible_reminder_at_is_null_for_a_converted_cart(): void
    {
        $user = User::factory()->create();
        $cart = $this->makeCart($user, ['status' => 'converted', 'converted_at' => now()]);

        $this->assertNull($cart->nextEligibleReminderAt());
    }
}
