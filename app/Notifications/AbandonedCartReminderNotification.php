<?php

namespace App\Notifications;

use App\Mail\AbandonedCartReminderMail;
use App\Models\Cart;
use App\Support\CartReminderConfig;
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
        $channels = ['mail'];

        if (CartReminderConfig::notificationEnabled()) {
            $channels[] = 'database';
        }

        return $channels;
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
            // Reuses the exact copy already used in the branded email
            // (emails.php) so the notification and email never drift apart.
            'title' => __('emails.cart_reminder_subject'),
            'message' => __('emails.cart_reminder_intro'),
            'url' => route('checkout.show'),
        ];
    }
}
