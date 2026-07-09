<?php

namespace App\Console\Commands;

use App\Jobs\SendAbandonedCartReminderJob;
use App\Services\AbandonedCartReminderService;
use Illuminate\Console\Command;

class SendAbandonedCartReminders extends Command
{
    protected $signature = 'carts:send-reminders';

    protected $description = 'Flip inactive carts to abandoned and dispatch reminder emails/notifications to eligible customers.';

    public function handle(AbandonedCartReminderService $service): int
    {
        $transitioned = $service->transitionAbandonedCarts();

        $eligible = $service->eligibleForReminder();

        foreach ($eligible as $cart) {
            SendAbandonedCartReminderJob::dispatch($cart);
        }

        $this->info("{$transitioned->count()} cart(s) transitioned to abandoned. {$eligible->count()} reminder(s) dispatched.");

        return self::SUCCESS;
    }
}
