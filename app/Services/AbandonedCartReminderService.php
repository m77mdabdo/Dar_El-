<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\User;
use App\Notifications\CartAbandonedAdminNotification;
use App\Support\CartReminderConfig;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;

class AbandonedCartReminderService
{
    /**
     * Flip any "active" cart that's been inactive past the configured
     * threshold to "abandoned", firing the admin notification(s) exactly
     * once per cart — at the transition, not on every subsequent run.
     *
     * @return Collection<int, Cart> the carts that were just transitioned
     */
    public function transitionAbandonedCarts(): Collection
    {
        $threshold = now()->subMinutes(CartReminderConfig::firstDelayMinutes());

        $candidates = Cart::where('status', 'active')
            ->where('items_count', '>', 0)
            ->where('last_activity_at', '<=', $threshold)
            ->with('user')
            ->get();

        if ($candidates->isEmpty()) {
            return $candidates;
        }

        Cart::whereIn('id', $candidates->pluck('id'))->update(['status' => 'abandoned']);

        $admins = User::admins();
        $highValueThreshold = config('cart.high_value_threshold');

        foreach ($candidates as $cart) {
            $cart->status = 'abandoned';

            Notification::send($admins, new CartAbandonedAdminNotification($cart, $cart->total >= $highValueThreshold));
        }

        return $candidates;
    }

    /**
     * @return Collection<int, Cart>
     */
    public function eligibleForReminder(): Collection
    {
        return Cart::eligibleForReminder()->with(['user', 'items'])->get();
    }
}
