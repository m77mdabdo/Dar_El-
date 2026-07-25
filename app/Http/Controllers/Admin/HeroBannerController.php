<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Banner;
use App\Services\ImageUploadService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin CRUD for the homepage hero — scoped to Banner::TYPE_HERO only
 * (offer/collection/category banners already render on the homepage with
 * no admin UI of their own; building that is a separate, unrequested
 * scope). Every query/write below is hard-scoped to type=hero so this
 * screen can never touch the other banner types.
 *
 * The live storefront hero (HomeController::index(), home.blade.php)
 * renders the first active row here (by sort_order) when one exists, and
 * falls back to the pre-existing hardcoded copy + home_hero_image Setting
 * otherwise — see Banner::booted() for the cache-busting that keeps that
 * in sync. Deliberately opt-in rather than automatic: DemoBannerSeeder
 * has a standing comment explaining why hero-type banners are never
 * seeded — an earlier version silently swapped a real store's homepage
 * to a demo slider. Only a genuine admin action on this screen can ever
 * create a hero banner.
 */
class HeroBannerController extends Controller
{
    public function __construct(protected ImageUploadService $imageUploader)
    {
    }

    public function index(): View
    {
        $banners = Banner::ofType(Banner::TYPE_HERO)->get();

        return view('admin.hero-banners.index', compact('banners'));
    }

    public function create(): View
    {
        return view('admin.hero-banners.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = collect($this->validated($request))->except('image')->all();
        // image is required on create — the underlying column is NOT NULL,
        // and validated() only makes it required when no existing banner
        // (with its own already-stored image) is passed in.

        if ($request->hasFile('image')) {
            $validated['image'] = $this->imageUploader->store($request->file('image'), 'banners');
        }

        $banner = Banner::create($validated + [
            'type' => Banner::TYPE_HERO,
            'sort_order' => Banner::ofType(Banner::TYPE_HERO)->max('sort_order') + 1,
        ]);

        ActivityLog::record('created', $banner, "Created hero banner {$banner->title_en}");

        return redirect()->route('admin.hero-banners.index')->with('status', __('hero_banners.created'));
    }

    public function edit(Banner $heroBanner): View
    {
        abort_unless($heroBanner->type === Banner::TYPE_HERO, 404);

        return view('admin.hero-banners.edit', ['banner' => $heroBanner]);
    }

    public function update(Request $request, Banner $heroBanner): RedirectResponse
    {
        abort_unless($heroBanner->type === Banner::TYPE_HERO, 404);

        $validated = collect($this->validated($request, $heroBanner))->except('image')->all();

        if ($request->hasFile('image')) {
            $validated['image'] = $this->imageUploader->replace($heroBanner->image, $request->file('image'), 'banners');
        }

        $heroBanner->update($validated);

        ActivityLog::record('updated', $heroBanner, "Updated hero banner {$heroBanner->title_en}");

        return redirect()->route('admin.hero-banners.index')->with('status', __('hero_banners.updated'));
    }

    public function destroy(Banner $heroBanner): RedirectResponse
    {
        abort_unless($heroBanner->type === Banner::TYPE_HERO, 404);

        $name = $heroBanner->title_en;
        $heroBanner->delete();

        ActivityLog::record('deleted', $heroBanner, "Deleted hero banner {$name}");

        return redirect()->route('admin.hero-banners.index')->with('status', __('hero_banners.deleted'));
    }

    public function toggleActive(Banner $heroBanner): RedirectResponse
    {
        abort_unless($heroBanner->type === Banner::TYPE_HERO, 404);

        $heroBanner->update(['is_active' => ! $heroBanner->is_active]);

        ActivityLog::record(
            $heroBanner->is_active ? 'enabled' : 'disabled',
            $heroBanner,
            "Toggled active status for hero banner {$heroBanner->title_en}"
        );

        return back()->with('status', $heroBanner->is_active
            ? __('hero_banners.activated')
            : __('hero_banners.deactivated'));
    }

    /**
     * Mirrors ProductController::reorderImages() — one PATCH per drop,
     * persisting the full ordered id list rather than a per-row number
     * field.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', Rule::exists('banners', 'id')->where('type', Banner::TYPE_HERO)],
        ]);

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json(['errors' => $validator->errors()], 422));
        }

        DB::transaction(function () use ($validator) {
            foreach ($validator->validated()['ids'] as $order => $id) {
                Banner::where('id', $id)->where('type', Banner::TYPE_HERO)->update(['sort_order' => $order]);
            }
        });

        return response()->json(['status' => 'ok']);
    }

    protected function validated(Request $request, ?Banner $heroBanner = null): array
    {
        $request->merge(['is_active' => $request->boolean('is_active')]);

        return $request->validate([
            'title_ar' => ['required', 'string', 'max:255'],
            'title_en' => ['required', 'string', 'max:255'],
            'subtitle_ar' => ['nullable', 'string', 'max:500'],
            'subtitle_en' => ['nullable', 'string', 'max:500'],
            'cta_text_ar' => ['nullable', 'string', 'max:100'],
            'cta_text_en' => ['nullable', 'string', 'max:100'],
            'link_url' => ['nullable', 'string', 'max:2048'],
            'is_active' => ['boolean'],
            // Required on create (the image column is NOT NULL); optional
            // on edit, where an existing stored image already satisfies it.
            'image' => [$heroBanner ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'image.image' => __('Please upload a valid image file.'),
            'image.mimes' => __('The image must be a JPG, PNG, or WEBP file.'),
            'image.max' => __('The image may not be larger than 4MB.'),
        ]);
    }
}
