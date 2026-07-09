<?php

namespace Tests\Feature;

use App\Jobs\SendAbandonedCartReminderJob;
use App\Models\Cart;
use App\Models\CartReminder;
use App\Models\Order;
use App\Models\User;
use App\Notifications\AbandonedCartReminderNotification;
use App\Notifications\CartAbandonedAdminNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AbandonedCartReminderCommandTest extends TestCase
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

    public function test_inactive_active_cart_transitions_to_abandoned_and_notifies_admin_once(): void
    {
        Notification::fake();
        Role::findOrCreate('admin', 'web');
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $cart = $this->makeCart($user, ['last_activity_at' => now()->subMinutes(130)]);

        $this->artisan('carts:send-reminders')->assertSuccessful();

        $this->assertSame('abandoned', $cart->fresh()->status);
        Notification::assertSentToTimes($admin, CartAbandonedAdminNotification::class, 1);

        // Running the command again must not re-fire the transition notification.
        $this->artisan('carts:send-reminders')->assertSuccessful();
        Notification::assertSentToTimes($admin, CartAbandonedAdminNotification::class, 1);
    }

    public function test_reminder_is_sent_for_eligible_abandoned_cart(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $cart = $this->makeCart($user, [
            'status' => 'abandoned',
            'last_activity_at' => now()->subHours(3),
        ]);

        $this->artisan('carts:send-reminders')->assertSuccessful();

        Notification::assertSentTo($user, AbandonedCartReminderNotification::class);
        $this->assertSame(1, $cart->fresh()->reminder_count);
        $this->assertSame(1, CartReminder::where('cart_id', $cart->id)->where('status', 'sent')->count());
    }

    public function test_reminder_is_not_sent_again_before_the_interval_elapses(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $cart = $this->makeCart($user, [
            'status' => 'abandoned',
            'last_activity_at' => now()->subHours(3),
            'reminder_count' => 1,
            'last_reminder_sent_at' => now()->subMinutes(30),
        ]);

        $this->artisan('carts:send-reminders')->assertSuccessful();

        Notification::assertNotSentTo($user, AbandonedCartReminderNotification::class);
        $this->assertSame(1, $cart->fresh()->reminder_count);
    }

    public function test_reminders_stop_once_the_cap_is_reached(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $cart = $this->makeCart($user, [
            'status' => 'abandoned',
            'last_activity_at' => now()->subHours(3),
            'reminder_count' => 3,
            'last_reminder_sent_at' => now()->subHours(5),
        ]);

        $this->artisan('carts:send-reminders')->assertSuccessful();

        Notification::assertNotSentTo($user, AbandonedCartReminderNotification::class);
        $this->assertSame(3, $cart->fresh()->reminder_count);
    }

    public function test_stale_queued_job_skips_a_cart_that_was_converted_in_the_meantime(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $order = Order::create([
            'order_number' => 'ORD-TEST-'.uniqid(),
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => '01000000000',
            'governorate' => 'Cairo',
            'city' => 'Nasr City',
            'address' => '123 Test St',
            'subtotal' => 500,
            'shipping_fee' => 0,
            'discount_amount' => 0,
            'total' => 500,
            'status' => 'pending',
            'payment_method' => 'cod',
        ]);

        $cart = $this->makeCart($user, [
            'status' => 'converted',
            'converted_at' => now(),
            'order_id' => $order->id,
        ]);

        // Simulate a job that was queued while the cart was still abandoned,
        // then only ran after the customer had already completed checkout.
        (new SendAbandonedCartReminderJob($cart))->handle();

        Notification::assertNotSentTo($user, AbandonedCartReminderNotification::class);
        $this->assertSame(0, $cart->fresh()->reminder_count);
    }
}
