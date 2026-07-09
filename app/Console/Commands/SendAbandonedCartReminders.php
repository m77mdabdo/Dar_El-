<?php

namespace App\Console\Commands;

use App\Jobs\SendAbandonedCartReminderJob;
use App\Services\AbandonedCartReminderService;
use App\Support\CartReminderConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendAbandonedCartReminders extends Command
{
    protected $signature = 'carts:send-reminders';

    protected $description = 'Flip inactive carts to abandoned and dispatch reminder emails/notifications to eligible customers.';

    /**
     * Runs frequently (every 30 minutes, see routes/console.php) and lets
     * eligibility — not the schedule itself — decide who actually gets a
     * reminder. The 1-hour-first / 4-hour-interval / 3-max rules all live
     * in CartReminderConfig/Cart::scopeEligibleForReminder, not here.
     */
    public function handle(AbandonedCartReminderService $service): int
    {
        $transitioned = $service->transitionAbandonedCarts();

        if (! CartReminderConfig::enabled()) {
            $message = "{$transitioned->count()} cart(s) transitioned to abandoned. Automatic reminders are disabled in Settings — no reminders dispatched.";
            $this->info($message);
            Log::info('carts:send-reminders: automatic reminders disabled, skipping dispatch', ['transitioned' => $transitioned->count()]);

            return self::SUCCESS;
        }

        $eligible = $service->eligibleForReminder();
        $dispatched = 0;
        $failed = 0;

        foreach ($eligible as $cart) {
            try {
                SendAbandonedCartReminderJob::dispatch($cart);
                $dispatched++;
            } catch (\Throwable $e) {
                $failed++;
                Log::error('carts:send-reminders: failed to dispatch reminder job', ['cart_id' => $cart->id, 'error' => $e->getMessage()]);
            }
        }

        $message = "{$transitioned->count()} cart(s) transitioned to abandoned. {$dispatched} reminder(s) dispatched.".($failed > 0 ? " {$failed} failed to dispatch." : '');

        $this->info($message);
        Log::info('carts:send-reminders completed', [
            'transitioned' => $transitioned->count(),
            'eligible' => $eligible->count(),
            'dispatched' => $dispatched,
            'failed_to_dispatch' => $failed,
        ]);

        return self::SUCCESS;
    }
}
