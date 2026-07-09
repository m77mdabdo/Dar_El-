<?php

namespace App\Notifications;

use App\Mail\AbandonedCartReminderMail;
use App\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class AbandonedCartReminderNotification extends Notification implements ShouldQueue
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
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): AbandonedCartReminderMail
    {
        return new AbandonedCartReminderMail($this->cart);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'cart_reminder',
            'cart_id' => $this->cart->id,
            'items_count' => $this->cart->items_count,
            'total' => $this->cart->total,
        ];
    }
}
