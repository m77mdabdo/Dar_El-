<?php

namespace App\Notifications;

use App\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CartAbandonedAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Cart $cart, public bool $isHighValue = false)
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
            'type' => $this->isHighValue ? 'high_value_cart_abandoned' : 'cart_abandoned',
            'cart_id' => $this->cart->id,
            'customer_name' => $this->cart->user?->name,
            'total' => $this->cart->total,
        ];
    }
}
