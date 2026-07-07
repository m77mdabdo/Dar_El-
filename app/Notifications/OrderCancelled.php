<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class OrderCancelled extends Notification implements ShouldQueue
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
            'type' => 'order_cancelled',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
        ];
    }
}
