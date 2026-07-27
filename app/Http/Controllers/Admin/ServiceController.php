<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Service;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin CRUD for the /services page — genuinely new (no prior model/
 * table existed). The public page (resources/views/pages/services.blade.php)
 * was a hardcoded grid of 6 service cards; only the admin-managed data
 * source was missing. Unlike Hero Banners/Testimonials/FAQ, /services
 * isn't cached anywhere (PageController::services() queries fresh every
 * request, matching about/return-policy), so there's no cache to bust
 * here. Gated by the same 'pages.manage' permission slug FAQ uses —
 * both are "static page content" in the same sense.
 */
class ServiceController extends Controller
{
    public function index(): View
    {
        $services = Service::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.services.index', compact('services'));
    }

    public function create(): View
    {
        return view('admin.services.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $service = Service::create($this->validated($request) + [
            'sort_order' => Service::max('sort_order') + 1,
        ]);

        ActivityLog::record('created', $service, "Created service {$service->title_en}");

        return redirect()->route('admin.services.index')->with('status', __('services.created'));
    }

    public function edit(Service $service): View
    {
        return view('admin.services.edit', compact('service'));
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $service->update($this->validated($request));

        ActivityLog::record('updated', $service, "Updated service {$service->title_en}");

        return redirect()->route('admin.services.index')->with('status', __('services.updated'));
    }

    public function destroy(Service $service): RedirectResponse
    {
        $name = $service->title_en;
        $service->delete();

        ActivityLog::record('deleted', $service, "Deleted service {$name}");

        return redirect()->route('admin.services.index')->with('status', __('services.deleted'));
    }

    public function toggleActive(Service $service): RedirectResponse
    {
        $service->update(['is_active' => ! $service->is_active]);

        ActivityLog::record(
            $service->is_active ? 'enabled' : 'disabled',
            $service,
            "Toggled active status for service {$service->title_en}"
        );

        return back()->with('status', $service->is_active
            ? __('services.activated')
            : __('services.deactivated'));
    }

    /**
     * Mirrors HeroBannerController::reorder()/FaqController::reorder().
     */
    public function reorder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', Rule::exists('services', 'id')],
        ]);

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json(['errors' => $validator->errors()], 422));
        }

        DB::transaction(function () use ($validator) {
            foreach ($validator->validated()['ids'] as $order => $id) {
                Service::where('id', $id)->update(['sort_order' => $order]);
            }
        });

        return response()->json(['status' => 'ok']);
    }

    protected function validated(Request $request): array
    {
        $request->merge(['is_active' => $request->boolean('is_active')]);

        return $request->validate([
            'title_ar' => ['required', 'string', 'max:255'],
            'title_en' => ['required', 'string', 'max:255'],
            'description_ar' => ['required', 'string', 'max:1000'],
            'description_en' => ['required', 'string', 'max:1000'],
            'icon' => ['required', Rule::in(array_keys(Service::ICONS))],
            'is_active' => ['boolean'],
        ]);
    }
}
