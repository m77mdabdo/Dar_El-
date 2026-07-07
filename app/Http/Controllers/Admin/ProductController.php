<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\ImageUploadService;
use App\Services\StockAlertService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function __construct(protected ImageUploadService $imageUploader, protected StockAlertService $stockAlerts)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Product::class);

        $products = Product::with(['category', 'sizes'])
            ->withSum('sizes as total_stock', 'stock')
            ->when($request->search, fn ($q) => $q->where('name_en', 'like', "%{$request->search}%"))
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

        $validated = collect($this->validated($request))->except(['images', 'image_url'])->all();

        $product = Product::create($validated + ['slug' => Str::slug($validated['name_en'])]);

        if ($request->hasFile('image_url')) {
            $product->update(['image_url' => $this->imageUploader->store($request->file('image_url'), "products/{$product->id}")]);
        }

        $this->syncSizes($product, $request);
        $this->uploadImages($product, $request);

        ActivityLog::record('created', $product, "Created product {$product->name_en}");

        return redirect()->route('admin.products.index')->with('status', 'Product created.');
    }

    public function edit(Product $product)
    {
        $this->authorize('update', $product);

        $categories = Category::orderBy('name_en')->get();
        $product->load('sizes', 'images');

        return view('admin.products.edit', compact('product', 'categories'));
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('update', $product);

        $validated = collect($this->validated($request, $product))->except(['images', 'image_url'])->all();

        if ($request->hasFile('image_url')) {
            $validated['image_url'] = $this->imageUploader->replace($product->image_url, $request->file('image_url'), "products/{$product->id}");
        }

        $product->update($validated + ['slug' => Str::slug($validated['name_en'])]);

        $this->syncSizes($product, $request);
        $this->uploadImages($product, $request);

        ActivityLog::record('updated', $product, "Updated product {$product->name_en}");

        return redirect()->route('admin.products.index')->with('status', 'Product updated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $name = $product->name_en;

        // Delete files explicitly: the FK cascade removes the rows at the DB level
        // without firing Eloquent's deleting event, so images would otherwise orphan.
        $product->images->each->delete();
        $product->delete();

        ActivityLog::record('deleted', $product, "Deleted product {$name}");

        return redirect()->route('admin.products.index')->with('status', 'Product deleted.');
    }

    public function destroyImage(Product $product, ProductImage $image): RedirectResponse
    {
        $this->authorize('update', $product);

        abort_unless($image->product_id === $product->id, 404);

        $image->delete();

        return back()->with('status', 'Image removed.');
    }

    public function updateImage(Request $request, Product $product, ProductImage $image): RedirectResponse
    {
        $this->authorize('update', $product);

        abort_unless($image->product_id === $product->id, 404);

        $validated = $request->validate(['sort_order' => ['required', 'integer']]);

        $image->update($validated);

        return back()->with('status', 'Image order updated.');
    }

    protected function validated(Request $request, ?Product $product = null): array
    {
        $request->merge([
            'is_active' => $request->boolean('is_active'),
            'is_featured' => $request->boolean('is_featured'),
        ]);

        return $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'compare_at_price' => ['nullable', 'integer', 'min:0'],
            'sku' => ['nullable', 'string', 'max:255'],
            'badge' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'is_featured' => ['boolean'],
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
