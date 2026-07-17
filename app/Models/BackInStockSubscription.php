<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackInStockSubscription extends Model
{
    protected $fillable = ['product_id', 'product_size_id', 'email', 'user_id', 'notified_at'];

    protected function casts(): array
    {
        return [
            'notified_at' => 'datetime',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function productSize(): BelongsTo
    {
        return $this->belongsTo(ProductSize::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
