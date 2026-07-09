<?php

namespace App\Services;

use App\Models\Product;

class ProductDeleter
{
    /**
     * Delete files explicitly before the model delete: the FK cascade removes
     * the rows at the DB level without firing Eloquent's deleting event, so
     * images would otherwise orphan on disk.
     */
    public function delete(Product $product): void
    {
        $product->images->each->delete();
        $product->delete();
    }
}
