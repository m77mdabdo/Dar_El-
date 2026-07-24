<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Cross-product stock overview — distinct from the per-product "Sizes &
 * Stock" tab (Admin\ProductController::updateSizes()), which edits one
 * product at a time. This is the "look at everything at once, catch what
 * needs restocking" screen; inline edits here submit to that exact same
 * action (see admin/inventory/index.blade.php), so there's only ever one
 * place that actually writes to product_sizes.
 */
class InventoryController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::query()
            ->with(['category:id,name_ar,name_en', 'images', 'sizes'])
            ->withSum('sizes as total_stock', 'stock')
            ->search($request->search)
            ->when($request->category_id, fn ($q) => $q->where('category_id', $request->category_id))
            ->filterByStockStatus($request->stock_status)
            ->when(
                $request->sort === 'stock_asc',
                fn ($q) => $q->orderBy('total_stock'),
                fn ($q) => $request->sort === 'stock_desc'
                    ? $q->orderByDesc('total_stock')
                    : $q->orderBy('name_en')
            )
            ->paginate(20)
            ->withQueryString();

        $categories = Category::orderBy('name_en')->get(['id', 'name_ar', 'name_en']);

        return view('admin.inventory.index', compact('products', 'categories'));
    }
}
