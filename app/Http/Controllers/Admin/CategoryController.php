<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct(protected ImageUploadService $imageUploader)
    {
    }

    public function index()
    {
        $this->authorize('viewAny', Category::class);

        $categories = Category::withCount('products')->orderBy('sort_order')->paginate(20);

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        $this->authorize('create', Category::class);

        return view('admin.categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Category::class);

        $validated = collect($this->validated($request))->except('image')->all();

        if ($request->hasFile('image')) {
            $validated['image'] = $this->imageUploader->store($request->file('image'), 'categories');
        }

        $category = Category::create($validated + ['slug' => Str::slug($validated['name_en'])]);

        ActivityLog::record('created', $category, "Created category {$category->name_en}");

        return redirect()->route('admin.categories.index')->with('status', __('categories.created'));
    }

    public function edit(Category $category)
    {
        $this->authorize('update', $category);

        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $this->authorize('update', $category);

        $validated = collect($this->validated($request))->except('image')->all();

        if ($request->hasFile('image')) {
            $validated['image'] = $this->imageUploader->replace($category->image, $request->file('image'), 'categories');
        }

        $category->update($validated + ['slug' => Str::slug($validated['name_en'])]);

        ActivityLog::record('updated', $category, "Updated category {$category->name_en}");

        return redirect()->route('admin.categories.index')->with('status', __('categories.updated'));
    }

    public function destroy(Category $category): RedirectResponse
    {
        $this->authorize('delete', $category);

        $productCount = $category->products()->count();
        if ($productCount > 0) {
            return back()->with('error', __('categories.cannot_delete_has_products', ['count' => $productCount]));
        }

        $name = $category->name_en;
        $category->delete();

        ActivityLog::record('deleted', $category, "Deleted category {$name}");

        return redirect()->route('admin.categories.index')->with('status', __('categories.deleted'));
    }

    protected function validated(Request $request): array
    {
        $request->merge(['is_active' => $request->boolean('is_active')]);

        return $request->validate([
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'sort_order' => ['nullable', 'integer'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'image.image' => __('Please upload a valid image file.'),
            'image.mimes' => __('The image must be a JPG, PNG, or WEBP file.'),
            'image.max' => __('The image may not be larger than 4MB.'),
        ]);
    }
}
