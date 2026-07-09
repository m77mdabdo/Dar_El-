<?php

namespace App\Notifications;

use App\Mail\ProductLowStockMail;
use App\Models\Product;
use App\Models\ProductSize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ProductLowStock extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Product $product, public ProductSize $size)
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

    public function toMail(object $notifiable): ProductLowStockMail
    {
        return new ProductLowStockMail($this->product, $this->size, $notifiable);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name_en,
            'size' => $this->size->size,
            'stock' => $this->size->stock,
        ];
    }
}
