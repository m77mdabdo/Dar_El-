<?php

namespace App\Notifications;

use App\Mail\NewOrderPlacedMail;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewOrderPlaced extends Notification implements ShouldQueue
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
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): NewOrderPlacedMail
    {
        return new NewOrderPlacedMail($this->order, $notifiable);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_order',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'total' => $this->order->total,
        ];
    }
}
