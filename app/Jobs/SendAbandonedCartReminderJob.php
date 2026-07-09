<?php

namespace App\Jobs;

use App\Models\Cart;
use App\Models\CartReminder;
use App\Models\User;
use App\Notifications\AbandonedCartReminderNotification;
use App\Notifications\CartReminderFailedAdminNotification;
use App\Support\CartReminderConfig;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendAbandonedCartReminderJob implements ShouldQueue
{
    use Queueable;

    /**
     * @param  bool  $force  Bypass the "must already be abandoned" and
     *                       reminder-cap gates. Used by admin-triggered
     *                       manual/bulk reminders, which are an explicit
     *                       human override rather than automated spam.
     */
    public function __construct(public Cart $cart, public bool $force = false)
    {
        //
    }

    public function handle(): void
    {
        $this->cart->refresh();

        Log::info('Cart reminder job started', [
            'cart_id' => $this->cart->id,
            'customer_email' => $this->cart->user?->email,
            'force' => $this->force,
        ]);

        if ($this->cart->items_count === 0) {
            Log::warning('Cart reminder job skipped: cart has no items', ['cart_id' => $this->cart->id]);

            return;
        }

        // Re-check eligibility: the cart may have been converted or emptied
        // between the command dispatching this job and the queue worker
        // actually running it. Force-sent (admin-triggered) reminders skip
        // this so an admin can resend even past the automated cap.
        if (! $this->force) {
            if ($this->cart->status !== 'abandoned') {
                Log::info('Cart reminder job skipped: cart is not abandoned', ['cart_id' => $this->cart->id, 'status' => $this->cart->status]);

                return;
            }

            if ($this->cart->reminder_count >= CartReminderConfig::maxReminders()) {
                Log::info('Cart reminder job skipped: reminder cap reached', ['cart_id' => $this->cart->id]);

                return;
            }
        }

        $this->cart->loadMissing('user', 'items');

        if (! $this->cart->user || ! $this->cart->user->email) {
            Log::error('Cart reminder job failed: customer has no email', ['cart_id' => $this->cart->id, 'user_id' => $this->cart->user_id]);

            CartReminder::create([
                'cart_id' => $this->cart->id,
                'user_id' => $this->cart->user_id,
                'channel' => 'mail',
                'source' => $this->force ? 'manual' : 'automatic',
                'status' => 'failed',
                'error_message' => 'Customer has no email address on file.',
            ]);

            return;
        }

        try {
            // AbandonedCartReminderNotification implements ShouldQueue, so a
            // plain ->notify() call would push the actual mail send onto the
            // real queue connection and return immediately regardless of
            // whether a worker is running. Force-sent (admin-triggered)
            // reminders must reflect the true send result in this same
            // request, so send them immediately via sendNow(), bypassing the
            // queue entirely. The automated background command keeps using
            // ->notify() (queued), since that path is expected to run behind
            // a real queue worker/cron.
            if ($this->force) {
                Notification::sendNow($this->cart->user, new AbandonedCartReminderNotification($this->cart));
            } else {
                $this->cart->user->notify(new AbandonedCartReminderNotification($this->cart));
            }

            $this->cart->increment('reminder_count');
            $this->cart->update(['last_reminder_sent_at' => now()]);

            CartReminder::create([
                'cart_id' => $this->cart->id,
                'user_id' => $this->cart->user_id,
                'channel' => 'mail',
                'source' => $this->force ? 'manual' : 'automatic',
                'sent_at' => now(),
                'status' => 'sent',
            ]);

            Log::info('Cart reminder sent successfully', [
                'cart_id' => $this->cart->id,
                'customer_email' => $this->cart->user->email,
            ]);
        } catch (\Throwable $e) {
            CartReminder::create([
                'cart_id' => $this->cart->id,
                'user_id' => $this->cart->user_id,
                'channel' => 'mail',
                'source' => $this->force ? 'manual' : 'automatic',
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            Log::error('Cart reminder failed to send', [
                'cart_id' => $this->cart->id,
                'customer_email' => $this->cart->user->email,
                'error' => $e->getMessage(),
            ]);

            Notification::send(User::admins(), new CartReminderFailedAdminNotification($this->cart, $e->getMessage()));
        }
    }
}
