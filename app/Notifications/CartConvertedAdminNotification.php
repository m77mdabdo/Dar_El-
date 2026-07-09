<?php

namespace App\Notifications;

use App\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class CartConvertedAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Cart $cart)
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
            'type' => 'cart_converted',
            'cart_id' => $this->cart->id,
            'customer_name' => $this->cart->user?->name,
            'order_id' => $this->cart->order_id,
        ];
    }
}
