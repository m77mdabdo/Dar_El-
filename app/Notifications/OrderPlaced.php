<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Customer-facing "your order was placed" record — database only. The
 * actual email is sent separately (see CheckoutController::store()) so a
 * queue delay on this notification never delays the customer's inbox
 * confirmation. Only dispatched for registered users (guests have no
 * notifiable account to attach a database row to); mirrors the existing
 * OrderCancelled notification's database-only, admin-facing counterpart.
 */
class OrderPlaced extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order)
    {
        //
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_placed',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total' => $this->order->total,
        ];
    }
}
