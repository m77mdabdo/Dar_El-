<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\BackInStockService;
use App\Services\ImageUploadService;
use App\Services\ProductDeleter;
use App\Services\StockAlertService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(
        protected ImageUploadService $imageUploader,
        protected StockAlertService $stockAlerts,
        protected BackInStockService $backInStock,
        protected ProductDeleter $productDeleter,
    ) {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::with(['category', 'sizes'])
            ->withSum('sizes as total_stock', 'stock')
            ->search($request->search)
            ->ofStatus($request->status)
            ->filterByStockStatus($request->stock_status)
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $this->authorize('create', Product::class);

        $categories = Category::orderBy('name_en')->get();

        return view('admin.products.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Product::class);

        $all = $this->validated($request);
        $status = $all['status'];
        $validated = collect($all)->except(['images', 'image_url', 'status'])->all();

        $product = Product::create($validated + ['slug' => Str::slug($validated['name_en'])]);
        $product->applyStatus($status);

        if ($request->hasFile('image_url')) {
            $product->update(['image_url' => $this->imageUploader->store($request->file('image_url'), "products/{$product->id}")]);
        }

        $this->syncSizes($product, $request);
        $this->uploadImages($product, $request);

        ActivityLog::record('created', $product, "Created product {$product->name_en}");

        // Drop straight into Guided Setup so a brand-new draft walks through
        // Options -> Variants -> Images -> SEO -> Publish in order, instead
        // of landing back on the list with everything still to configure.
        return redirect()->route('admin.products.edit', ['product' => $product, 'wizard' => 1])
            ->with('status', __('products.created'));
    }

    public function edit(Request $request, Product $product)
    {
        $this->authorize('update', $product);

        $categories = Category::orderBy('name_en')->get();
        $product->load('sizes', 'images', 'options.values.images', 'variants.values.option');
        $wizard = $request->boolean('wizard');

        return view('admin.products.edit', compact('product', 'categories', 'wizard'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $all = $this->validated($request, $product);
        $status = $all['status'];
        $validated = collect($all)->except(['images', 'image_url', 'status'])->all();

        if ($request->hasFile('image_url')) {
            $validated['image_url'] = $this->imageUploader->replace($product->image_url, $request->file('image_url'), "products/{$product->id}");
        }

        $product->update($validated + ['slug' => Str::slug($validated['name_en'])]);
        $product->applyStatus($status);

        $this->syncSizes($product, $request);
        $this->uploadImages($product, $request);

        ActivityLog::record('updated', $product, "Updated product {$product->name_en}");

        return redirect()->route('admin.products.index')->with('status', __('products.updated'));
    }

    /**
     * Partial-payload save for the autosave JS: only the submitted fields
     * are validated and persisted. Validates manually (rather than
     * $request->validate()) because this app's exception handler only
     * auto-renders ValidationException as JSON for api/* routes (see
     * bootstrap/app.php's shouldRenderJsonWhen) — this admin/* endpoint
     * needs to return JSON itself either way.
     */
    public function autosave(Request $request, Product $product): JsonResponse
    {
        $this->authorize('update', $product);

        $rules = collect($this->fieldRules())->only(array_keys($request->all()))->all();

        if (array_key_exists('is_featured', $rules)) {
            $request->merge(['is_featured' => $request->boolean('is_featured')]);
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json(['errors' => $validator->errors()], 422));
        }

        $validated = $validator->validated();
        $status = $validated['status'] ?? null;
        unset($validated['status']);

        if ($validated !== []) {
            $product->update($validated);
        }

        if ($status !== null) {
            $product->applyStatus($status);
        }

        return response()->json(['status' => 'ok', 'updated_at' => $product->fresh()->updated_at->toIso8601String()]);
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        if ($reason = $this->productDeleter->blockingReason($product)) {
            return back()->with('error', $reason);
        }

        $name = $product->name_en;

        $this->productDeleter->delete($product);

        ActivityLog::record('deleted', $product, "Deleted product {$name}");

        return redirect()->route('admin.products.index')->with('status', __('products.deleted'));
    }

    public function destroyImage(Product $product, ProductImage $image): RedirectResponse
    {
        $this->authorize('manageImages', $product);

        abort_unless($image->product_id === $product->id, 404);

        $image->delete();

        return back()->with('status', __('products.image_removed'));
    }

    public function updateImage(Request $request, Product $product, ProductImage $image): RedirectResponse
    {
        $this->authorize('manageImages', $product);

        abort_unless($image->product_id === $product->id, 404);

        $validated = $request->validate(['sort_order' => ['required', 'integer']]);

        $image->update($validated);

        return back()->with('status', __('products.image_order_updated'));
    }

    /**
     * Drag-reorder for the gallery: accepts the full ordered list of image
     * ids and assigns sort_order 0..n-1 in one transaction, replacing the
     * old one-PATCH-per-image number-box workflow (updateImage() above
     * stays available for any non-JS fallback).
     */
    public function reorderImages(Request $request, Product $product): JsonResponse
    {
        $this->authorize('manageImages', $product);

        $validator = Validator::make($request->all(), [
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', Rule::exists('product_images', 'id')->where('product_id', $product->id)],
        ]);

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json(['errors' => $validator->errors()], 422));
        }

        DB::transaction(function () use ($validator, $product) {
            foreach ($validator->validated()['ids'] as $order => $id) {
                ProductImage::where('id', $id)->where('product_id', $product->id)->update(['sort_order' => $order]);
            }
        });

        return response()->json(['status' => 'ok']);
    }

    /**
     * One-click "set as cover": points the dedicated image_url field at this
     * gallery image's file (copied, not moved, so removing it from the
     * gallery later doesn't also delete the cover).
     */
    public function setCoverImage(Product $product, ProductImage $image): RedirectResponse
    {
        $this->authorize('manageImages', $product);

        abort_unless($image->product_id === $product->id, 404);

        $product->update(['image_url' => $this->imageUploader->copy($image->path, "products/{$product->id}")]);

        return back()->with('status', __('products.cover_updated'));
    }

    /**
     * Shared field rules for both the full form save and the partial
     * autosave endpoint. Image fields are excluded from autosave (they
     * never arrive as JSON), so autosave filters this down to whichever
     * keys are actually present in its payload.
     */
    protected function fieldRules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'compare_at_price' => ['nullable', 'integer', 'min:0'],
            'offer_ends_at' => ['nullable', 'date'],
            'sku' => ['nullable', 'string', 'max:255'],
            'badge' => ['nullable', 'string', 'max:255'],
            'is_featured' => ['boolean'],
            'status' => ['required', 'in:draft,scheduled,published,archived'],
            'scheduled_publish_at' => ['nullable', 'date', 'required_if:status,scheduled'],
            'meta_title_ar' => ['nullable', 'string', 'max:255'],
            'meta_title_en' => ['nullable', 'string', 'max:255'],
            'meta_description_ar' => ['nullable', 'string', 'max:500'],
            'meta_description_en' => ['nullable', 'string', 'max:500'],
            'sku_prefix' => ['nullable', 'string', 'max:50'],
            'default_stock' => ['nullable', 'integer', 'min:0'],
            'default_low_stock_threshold' => ['nullable', 'integer', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    protected function validated(Request $request, ?Product $product = null): array
    {
        $request->merge([
            'is_featured' => $request->boolean('is_featured'),
            // An empty datetime-local input submits as '' — nullable-and-
            // empty still passes the 'date' rule, but Eloquent's datetime
            // cast hands that raw empty string straight to the query
            // builder, which MySQL rejects outright (SQLite is lenient
            // about it instead, masking this in tests unless explicitly
            // checked against real MySQL — confirmed identically for both
            // fields below). Coerce to a real null before validation so
            // "leave empty to disable/unschedule" actually clears the
            // column instead of throwing in production.
            'offer_ends_at' => $request->offer_ends_at ?: null,
            'scheduled_publish_at' => $request->scheduled_publish_at ?: null,
        ]);

        return $request->validate($this->fieldRules() + [
            'image_url' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'images' => ['nullable', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'image_url.image' => __('Please upload a valid image file.'),
            'image_url.mimes' => __('The image must be a JPG, PNG, or WEBP file.'),
            'image_url.max' => __('The image may not be larger than 4MB.'),
            'images.*.image' => __('Please upload valid image files.'),
            'images.*.mimes' => __('Images must be JPG, PNG, or WEBP files.'),
            'images.*.max' => __('Each image may not be larger than 4MB.'),
        ]);
    }

    protected function syncSizes(Product $product, Request $request): void
    {
        foreach ((array) $request->input('sizes', []) as $size => $stock) {
            if ($size === '') {
                continue;
            }

            $newStock = max(0, (int) $stock);
            $before = $product->sizes()->where('size', $size)->value('stock');

            $productSize = $product->sizes()->updateOrCreate(['size' => $size], ['stock' => $newStock]);

            if ($before !== null) {
                $this->stockAlerts->checkThreshold($product, $productSize, $before, $newStock);
                $this->backInStock->checkAndNotify($product, $productSize, $before, $newStock);
            }
        }
    }

    protected function uploadImages(Product $product, Request $request): void
    {
        if (! $request->hasFile('images')) {
            return;
        }

        $nextOrder = ($product->images()->max('sort_order') ?? -1) + 1;

        foreach ($request->file('images') as $file) {
            $path = $this->imageUploader->store($file, "products/{$product->id}");

            $product->images()->create([
                'path' => $path,
                'sort_order' => $nextOrder++,
            ]);
        }
    }
}
