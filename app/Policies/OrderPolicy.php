<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->hasAnyRole(['admin', 'super_admin']) ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAdminAccess('orders.view');
    }

    /**
     * Shared by both the customer-facing show/invoice-download routes
     * (ownership check) and the admin order list — someone with only
     * `orders.invoice` (no general `orders.view`) can still reach this,
     * since the app doesn't have a separate "view order" vs "download
     * invoice" controller action today.
     */
    public function view(User $user, Order $order): bool
    {
        return $user->id === $order->user_id
            || $user->hasAdminAccess('orders.view')
            || $user->hasAdminAccess('orders.invoice');
    }

    public function updateStatus(User $user, Order $order): bool
    {
        return $user->hasAdminAccess('orders.update_status');
    }
}
