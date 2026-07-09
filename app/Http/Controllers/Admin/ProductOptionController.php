<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductOption;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductOptionController extends Controller
{
    public function store(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $product->options()->create($validated + ['sort_order' => $validated['sort_order'] ?? $product->options()->count()]);

        return back()->with('status', __('product_options.option_created'));
    }

    public function update(Request $request, Product $product, ProductOption $option): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless($option->product_id === $product->id, 404);

        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $option->update($validated);

        return back()->with('status', __('product_options.option_updated'));
    }

    public function destroy(Product $product, ProductOption $option): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless($option->product_id === $product->id, 404);

        // A variant is only meaningful as a combination of option values, so any
        // variant depending on one of this option's values must be removed too —
        // otherwise it would be left representing a partial/incomplete combination.
        $product->variants()
            ->whereHas('values', fn ($q) => $q->where('product_option_id', $option->id))
            ->get()
            ->each->delete();

        $option->delete();

        return back()->with('status', __('product_options.option_deleted'));
    }
}
