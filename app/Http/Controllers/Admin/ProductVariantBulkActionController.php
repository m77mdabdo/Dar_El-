<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\ImageUploadService;
use App\Services\VariantSkuGenerator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * One-click mass operations for the variant grid — additive next to the
 * existing PATCH admin.products.{product}.variants.bulk (full-grid
 * row-by-row save), which stays exactly as-is for the "edit every visible
 * row, then save" workflow. This endpoint is for acting on a selection
 * without touching every row's inputs first.
 */
class ProductVariantBulkActionController extends Controller
{
    public function __construct(
        protected ImageUploadService $imageUploader,
        protected VariantSkuGenerator $skuGenerator,
    ) {
    }

    /**
     * Validates manually (rather than $request->validate()) because this
     * app's exception handler only auto-renders ValidationException as JSON
     * for api/* routes (see bootstrap/app.php's shouldRenderJsonWhen) — this
     * admin/* endpoint needs to return JSON itself either way.
     */
    public function handle(Request $request, Product $product): JsonResponse
    {
        $this->authorize('manageVariants', $product);

        $validator = Validator::make($request->all(), [
            'action' => ['required', Rule::in([
                'set_stock', 'adjust_stock', 'set_price', 'set_sale_price',
                'activate', 'deactivate', 'generate_skus', 'duplicate', 'delete',
            ])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'params.stock' => ['required_if:action,set_stock', 'integer', 'min:0'],
            'params.delta' => ['required_if:action,adjust_stock', 'integer'],
            'params.price_override' => ['nullable', 'integer', 'min:0'],
            'params.sale_price' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json(['errors' => $validator->errors()], 422));
        }

        $validated = $validator->validated();
        $params = $validated['params'] ?? [];

        $variants = $product->variants()->whereIn('id', $validated['ids'])->with('values.option')->get();

        foreach ($variants as $variant) {
            match ($validated['action']) {
                'set_stock' => $variant->update(['stock' => $params['stock']]),
                'adjust_stock' => $variant->update(['stock' => max(0, $variant->stock + $params['delta'])]),
                'set_price' => $variant->update(['price_override' => $params['price_override'] ?? null]),
                'set_sale_price' => $variant->update(['sale_price' => $params['sale_price'] ?? null]),
                'activate' => $variant->update(['is_active' => true]),
                'deactivate' => $variant->update(['is_active' => false]),
                'generate_skus' => $this->generateSku($product, $variant),
                'duplicate' => $this->duplicate($product, $variant),
                'delete' => $variant->delete(),
            };
        }

        return response()->json(['status' => 'ok', 'count' => $variants->count()]);
    }

    protected function generateSku(Product $product, ProductVariant $variant): void
    {
        $existingSkus = ProductVariant::whereNotNull('sku')->where('id', '!=', $variant->id)->pluck('sku')->all();

        $variant->update(['sku' => $this->skuGenerator->build($product, $variant->values, $existingSkus)]);
    }

    protected function duplicate(Product $product, ProductVariant $variant): void
    {
        $copy = $product->variants()->create([
            'sku' => null,
            'barcode' => $variant->barcode,
            'price_override' => $variant->price_override,
            'sale_price' => $variant->sale_price,
            'stock' => $variant->stock,
            'weight' => $variant->weight,
            'low_stock_threshold' => $variant->low_stock_threshold,
            'image' => $variant->image ? $this->imageUploader->copy($variant->image, "products/{$product->id}/variants") : null,
            'is_active' => false,
        ]);

        $copy->values()->attach($variant->values->pluck('id'));
    }
}
