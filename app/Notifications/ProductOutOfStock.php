<?php

namespace App\Notifications;

use App\Mail\ProductOutOfStockMail;
use App\Models\Product;
use App\Models\ProductSize;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ProductOutOfStock extends Notification implements ShouldQueue
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

    public function toMail(object $notifiable): ProductOutOfStockMail
    {
        return new ProductOutOfStockMail($this->product, $this->size, $notifiable);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'out_of_stock',
            'product_id' => $this->product->id,
            'product_name' => $this->product->name_en,
            'size' => $this->size->size,
        ];
    }
}
