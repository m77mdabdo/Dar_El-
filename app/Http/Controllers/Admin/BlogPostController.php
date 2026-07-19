<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BlogPost;
use App\Services\ImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class BlogPostController extends Controller
{
    public function __construct(protected ImageUploadService $imageUploader)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', BlogPost::class);

        $posts = BlogPost::when($request->search, fn ($q) => $q->where('title_en', 'like', "%{$request->search}%"))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.blog.index', compact('posts'));
    }

    public function create()
    {
        $this->authorize('create', BlogPost::class);

        return view('admin.blog.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', BlogPost::class);

        $validated = collect($this->validated($request))->except('cover_image')->all();

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $this->imageUploader->store($request->file('cover_image'), 'blog');
        }

        $post = BlogPost::create($validated + ['slug' => Str::slug($validated['title_en'])]);

        ActivityLog::record('created', $post, "Created blog post {$post->title_en}");

        return redirect()->route('admin.blog.index')->with('status', __('blog.created'));
    }

    public function edit(BlogPost $post)
    {
        $this->authorize('update', $post);

        return view('admin.blog.edit', compact('post'));
    }

    public function update(Request $request, BlogPost $post): RedirectResponse
    {
        $this->authorize('update', $post);

        $validated = collect($this->validated($request))->except('cover_image')->all();

        if ($request->hasFile('cover_image')) {
            $validated['cover_image'] = $this->imageUploader->replace($post->cover_image, $request->file('cover_image'), 'blog');
        }

        $post->update($validated + ['slug' => Str::slug($validated['title_en'])]);

        ActivityLog::record('updated', $post, "Updated blog post {$post->title_en}");

        return redirect()->route('admin.blog.index')->with('status', __('blog.updated'));
    }

    public function destroy(BlogPost $post): RedirectResponse
    {
        $this->authorize('delete', $post);

        $title = $post->title_en;
        $post->delete();

        ActivityLog::record('deleted', $post, "Deleted blog post {$title}");

        return redirect()->route('admin.blog.index')->with('status', __('blog.deleted'));
    }

    protected function validated(Request $request): array
    {
        $request->merge([
            'is_published' => $request->boolean('is_published'),
            // An empty datetime-local input submits as '' — nullable-and-
            // empty still passes the 'date' rule, but Eloquent's datetime
            // cast hands that raw empty string straight to the query
            // builder, which MySQL rejects outright (SQLite is lenient
            // about it instead, masking this in tests unless explicitly
            // checked against real MySQL). Coerce to a real null before
            // validation so "leave empty" actually clears the column
            // instead of throwing in production — same fix already
            // applied to Product's offer_ends_at/scheduled_publish_at.
            'published_at' => $request->published_at ?: null,
        ]);

        return $request->validate([
            'title_ar' => ['required', 'string', 'max:255'],
            'title_en' => ['required', 'string', 'max:255'],
            'excerpt_ar' => ['nullable', 'string'],
            'excerpt_en' => ['nullable', 'string'],
            'body_ar' => ['required', 'string'],
            'body_en' => ['required', 'string'],
            'is_published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'meta_title_ar' => ['nullable', 'string', 'max:255'],
            'meta_title_en' => ['nullable', 'string', 'max:255'],
            'meta_description_ar' => ['nullable', 'string', 'max:500'],
            'meta_description_en' => ['nullable', 'string', 'max:500'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'cover_image.image' => __('Please upload a valid image file.'),
            'cover_image.mimes' => __('The image must be a JPG, PNG, or WEBP file.'),
            'cover_image.max' => __('The image may not be larger than 4MB.'),
        ]);
    }
}
