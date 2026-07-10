<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Services\ImageUploadService;
use App\Services\VariantSkuGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductVariantController extends Controller
{
    protected const MAX_VARIANTS = 500;

    public function __construct(
        protected ImageUploadService $imageUploader,
        protected VariantSkuGenerator $skuGenerator,
    ) {
    }

    public function generate(Product $product): RedirectResponse
    {
        $this->authorize('manageVariants', $product);

        $options = $product->options()->with(['values' => fn ($q) => $q->where('is_active', true)])->get();

        $optionValueIds = $options->map(fn ($option) => $option->values->pluck('id')->all())
            ->filter(fn ($ids) => count($ids) > 0)
            ->values()
            ->all();

        if (count($optionValueIds) === 0) {
            throw ValidationException::withMessages(['options' => __('product_options.no_options_to_generate')]);
        }

        $combinations = array_reduce($optionValueIds, function ($carry, $valueIds) {
            $result = [];
            foreach ($carry as $combo) {
                foreach ($valueIds as $valueId) {
                    $result[] = [...$combo, $valueId];
                }
            }

            return $result;
        }, [[]]);

        if (count($combinations) > self::MAX_VARIANTS) {
            throw ValidationException::withMessages([
                'options' => __('product_options.too_many_combinations', ['count' => count($combinations), 'max' => self::MAX_VARIANTS]),
            ]);
        }

        $existingSignatures = $product->variants()->with('values')->get()
            ->map(fn (ProductVariant $variant) => $variant->values->pluck('id')->sort()->implode(','))
            ->all();

        $valuesById = ProductOptionValue::whereIn('id', collect($optionValueIds)->flatten()->all())->get()->keyBy('id');

        // sku is unique across ALL products (product_variants table has no
        // per-product scoping on the column), so collision-checking must
        // look globally, not just at this product's own variants.
        $existingSkus = ProductVariant::whereNotNull('sku')->pluck('sku')->all();

        $created = 0;

        foreach ($combinations as $combo) {
            $signature = collect($combo)->sort()->implode(',');

            if (in_array($signature, $existingSignatures, true)) {
                continue;
            }

            $values = collect($combo)->map(fn ($id) => $valuesById->get($id))->filter()->values();
            $sku = $this->skuGenerator->build($product, $values, $existingSkus);

            $variant = $product->variants()->create([
                'sku' => $sku,
                'stock' => $product->default_stock ?? 0,
                'low_stock_threshold' => $product->default_low_stock_threshold,
                'weight' => $product->weight,
                'is_active' => true,
            ]);
            $variant->values()->attach($combo);
            $created++;

            if ($sku) {
                $existingSkus[] = $sku;
            }
        }

        return back()->with('status', __('product_options.variants_generated', ['count' => $created]));
    }

    public function update(Request $request, Product $product, ProductVariant $variant): RedirectResponse
    {
        $this->authorize('manageVariants', $product);
        abort_unless($variant->product_id === $product->id, 404);

        $validated = $this->validated($request, $variant);

        if ($request->hasFile('image')) {
            $validated['image'] = $this->imageUploader->replace($variant->image, $request->file('image'), "products/{$product->id}/variants");
        } else {
            unset($validated['image']);
        }

        $variant->update($validated);

        return back()->with('status', __('product_options.variant_updated'));
    }

    public function bulkUpdate(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('manageVariants', $product);

        $rows = $request->validate([
            'variants' => ['required', 'array'],
            'variants.*.id' => ['required', 'integer'],
            'variants.*.sku' => ['nullable', 'string', 'max:255'],
            'variants.*.barcode' => ['nullable', 'string', 'max:255'],
            'variants.*.stock' => ['required', 'integer', 'min:0'],
            'variants.*.price_override' => ['nullable', 'integer', 'min:0'],
            'variants.*.sale_price' => ['nullable', 'integer', 'min:0'],
            'variants.*.weight' => ['nullable', 'numeric', 'min:0'],
            'variants.*.low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'variants.*.is_active' => ['nullable', 'boolean'],
        ])['variants'];

        foreach ($rows as $row) {
            $variant = $product->variants()->find($row['id']);

            if (! $variant) {
                continue;
            }

            $variant->update([
                'sku' => $row['sku'] ?? null,
                'barcode' => $row['barcode'] ?? null,
                'stock' => $row['stock'],
                'price_override' => $row['price_override'] ?? null,
                'sale_price' => $row['sale_price'] ?? null,
                'weight' => $row['weight'] ?? null,
                'low_stock_threshold' => $row['low_stock_threshold'] ?? null,
                'is_active' => $row['is_active'] ?? false,
            ]);
        }

        return back()->with('status', __('product_options.variants_updated'));
    }

    public function destroy(Product $product, ProductVariant $variant): RedirectResponse
    {
        $this->authorize('manageVariants', $product);
        abort_unless($variant->product_id === $product->id, 404);

        $variant->delete();

        return back()->with('status', __('product_options.variant_deleted'));
    }

    protected function validated(Request $request, ProductVariant $variant): array
    {
        $request->merge(['is_active' => $request->boolean('is_active')]);

        return $request->validate([
            'sku' => ['nullable', 'string', 'max:255', 'unique:product_variants,sku,'.$variant->id],
            'barcode' => ['nullable', 'string', 'max:255'],
            'price_override' => ['nullable', 'integer', 'min:0'],
            'sale_price' => ['nullable', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'sku.unique' => __('product_options.sku_taken'),
            'image.image' => __('Please upload a valid image file.'),
            'image.mimes' => __('The image must be a JPG, PNG, or WEBP file.'),
            'image.max' => __('The image may not be larger than 4MB.'),
        ]);
    }
}
