<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class InvoiceGenerationFailedAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Order $order, public string $errorMessage)
    {
        // Grouped with the invoice workflow's own queue rather than
        // 'default' — see GenerateAndSendInvoice for why redeclaring
        // Queueable's `$queue` property directly isn't possible.
        $this->queue = 'invoices';
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
            'type' => 'invoice_generation_failed',
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'error' => $this->errorMessage,
        ];
    }
}
