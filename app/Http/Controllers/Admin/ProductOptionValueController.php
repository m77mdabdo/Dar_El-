<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductOptionValueImage;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductOptionValueController extends Controller
{
    public function __construct(protected ImageUploadService $imageUploader)
    {
    }

    public function store(Request $request, Product $product, ProductOption $option): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless($option->product_id === $product->id, 404);

        $validated = $this->validated($request);

        if ($request->hasFile('swatch_image')) {
            $validated['swatch_image'] = $this->imageUploader->store($request->file('swatch_image'), "products/{$product->id}/options");
        }

        $option->values()->create($validated + ['sort_order' => $validated['sort_order'] ?? $option->values()->count()]);

        return back()->with('status', __('product_options.value_created'));
    }

    public function update(Request $request, Product $product, ProductOption $option, ProductOptionValue $value): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless($option->product_id === $product->id && $value->product_option_id === $option->id, 404);

        $validated = $this->validated($request);

        if ($request->hasFile('swatch_image')) {
            $validated['swatch_image'] = $this->imageUploader->replace($value->swatch_image, $request->file('swatch_image'), "products/{$product->id}/options");
        } else {
            unset($validated['swatch_image']);
        }

        $value->update($validated);

        return back()->with('status', __('product_options.value_updated'));
    }

    public function destroy(Product $product, ProductOption $option, ProductOptionValue $value): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless($option->product_id === $product->id && $value->product_option_id === $option->id, 404);

        // Same reasoning as ProductOptionController::destroy(): a variant tied to
        // this value would be left representing an incomplete combination.
        $product->variants()
            ->whereHas('values', fn ($q) => $q->where('product_option_values.id', $value->id))
            ->get()
            ->each->delete();

        $value->delete();

        return back()->with('status', __('product_options.value_deleted'));
    }

    public function storeImages(Request $request, Product $product, ProductOption $option, ProductOptionValue $value): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless($option->product_id === $product->id && $value->product_option_id === $option->id, 404);

        $request->validate([
            'images' => ['required', 'array'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'images.*.image' => __('Please upload valid image files.'),
            'images.*.mimes' => __('Images must be JPG, PNG, or WEBP files.'),
            'images.*.max' => __('Each image may not be larger than 4MB.'),
        ]);

        $nextOrder = ($value->images()->max('sort_order') ?? -1) + 1;

        foreach ($request->file('images') as $file) {
            $value->images()->create([
                'path' => $this->imageUploader->store($file, "products/{$product->id}/options"),
                'sort_order' => $nextOrder++,
            ]);
        }

        return back()->with('status', __('product_options.images_uploaded'));
    }

    public function destroyImage(Product $product, ProductOption $option, ProductOptionValue $value, ProductOptionValueImage $image): RedirectResponse
    {
        $this->authorize('update', $product);
        abort_unless(
            $option->product_id === $product->id
            && $value->product_option_id === $option->id
            && $image->product_option_value_id === $value->id,
            404
        );

        $image->delete();

        return back()->with('status', __('product_options.image_removed'));
    }

    protected function validated(Request $request): array
    {
        $request->merge(['is_active' => $request->boolean('is_active', true)]);

        return $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
            'hex_color' => ['nullable', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'swatch_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'hex_color.regex' => __('product_options.hex_color_invalid'),
            'swatch_image.image' => __('Please upload a valid image file.'),
            'swatch_image.mimes' => __('The image must be a JPG, PNG, or WEBP file.'),
            'swatch_image.max' => __('The image may not be larger than 4MB.'),
        ]);
    }
}
