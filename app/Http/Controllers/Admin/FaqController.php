<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Faq;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin CRUD for FAQs — genuinely new (no prior model/table existed).
 * The public accordion UI itself (resources/views/partials/faq-accordion.blade.php)
 * already existed and is reused as-is here; only the admin-managed data
 * source and the dedicated /faq page were missing. Gated by the single
 * pre-existing 'pages.manage' permission slug (already in
 * config/permission_groups.php, previously unused by any route) rather
 * than a Policy — same middleware convention as Settings/Shipping
 * Methods/Hero Banners.
 */
class FaqController extends Controller
{
    public function index(): View
    {
        $faqs = Faq::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.faqs.index', compact('faqs'));
    }

    public function create(): View
    {
        return view('admin.faqs.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $faq = Faq::create($validated + [
            'sort_order' => Faq::max('sort_order') + 1,
        ]);

        ActivityLog::record('created', $faq, "Created FAQ #{$faq->id}");

        return redirect()->route('admin.faqs.index')->with('status', __('faqs.created'));
    }

    public function edit(Faq $faq): View
    {
        return view('admin.faqs.edit', compact('faq'));
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $faq->update($this->validated($request));

        ActivityLog::record('updated', $faq, "Updated FAQ #{$faq->id}");

        return redirect()->route('admin.faqs.index')->with('status', __('faqs.updated'));
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $faq->delete();

        ActivityLog::record('deleted', $faq, "Deleted FAQ #{$faq->id}");

        return redirect()->route('admin.faqs.index')->with('status', __('faqs.deleted'));
    }

    public function toggleActive(Faq $faq): RedirectResponse
    {
        $faq->update(['is_active' => ! $faq->is_active]);

        ActivityLog::record(
            $faq->is_active ? 'enabled' : 'disabled',
            $faq,
            "Toggled active status for FAQ #{$faq->id}"
        );

        return back()->with('status', $faq->is_active
            ? __('faqs.activated')
            : __('faqs.deactivated'));
    }

    /**
     * Mirrors ProductController::reorderImages() / HeroBannerController::reorder().
     */
    public function reorder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', Rule::exists('faqs', 'id')],
        ]);

        if ($validator->fails()) {
            throw new HttpResponseException(response()->json(['errors' => $validator->errors()], 422));
        }

        DB::transaction(function () use ($validator) {
            foreach ($validator->validated()['ids'] as $order => $id) {
                Faq::where('id', $id)->update(['sort_order' => $order]);
            }
        });

        return response()->json(['status' => 'ok']);
    }

    protected function validated(Request $request): array
    {
        $request->merge(['is_active' => $request->boolean('is_active')]);

        return $request->validate([
            'question_ar' => ['required', 'string', 'max:500'],
            'question_en' => ['required', 'string', 'max:500'],
            'answer_ar' => ['required', 'string', 'max:5000'],
            'answer_en' => ['required', 'string', 'max:5000'],
            'is_active' => ['boolean'],
        ]);
    }
}
