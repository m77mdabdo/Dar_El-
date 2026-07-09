<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Product;
use App\Services\ProductDeleter;
use App\Services\ProductDuplicator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ProductBulkActionController extends Controller
{
    public function __construct(
        protected ProductDeleter $productDeleter,
        protected ProductDuplicator $productDuplicator,
    ) {
    }

    /**
     * Validates manually (rather than $request->validate()) because this
     * app's exception handler only auto-renders ValidationException as JSON
     * for api/* routes (see bootstrap/app.php's shouldRenderJsonWhen) — this
     * admin/* endpoint needs to return JSON itself either way.
     */
    public function handle(Request $request): JsonResponse
    {
        $this->authorize('create', Product::class);

        $validator = Validator::make($request->all(), [
            'action' => ['required', Rule::in(['publish', 'archive', 'delete', 'duplicate'])],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:products,id'],
        ]);

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json(['errors' => $validator->errors()], 422));
        }

        $validated = $validator->validated();

        $products = Product::whereIn('id', $validated['ids'])->get();

        foreach ($products as $product) {
            $this->authorize($validated['action'] === 'delete' ? 'delete' : 'update', $product);

            match ($validated['action']) {
                'publish' => $this->publish($product),
                'archive' => $this->archive($product),
                'delete' => $this->delete($product),
                'duplicate' => $this->duplicateProduct($product),
            };
        }

        return response()->json(['status' => 'ok', 'count' => $products->count()]);
    }

    protected function publish(Product $product): void
    {
        $product->applyStatus(Product::STATUS_PUBLISHED);
        ActivityLog::record('published', $product, "Published product {$product->name_en}");
    }

    protected function archive(Product $product): void
    {
        $product->applyStatus(Product::STATUS_ARCHIVED);
        ActivityLog::record('archived', $product, "Archived product {$product->name_en}");
    }

    protected function delete(Product $product): void
    {
        $name = $product->name_en;
        $this->productDeleter->delete($product);
        ActivityLog::record('deleted', $product, "Deleted product {$name}");
    }

    protected function duplicateProduct(Product $product): void
    {
        $copy = $this->productDuplicator->duplicate($product);
        ActivityLog::record('duplicated', $copy, "Duplicated product {$product->name_en} as {$copy->name_en}");
    }
}
