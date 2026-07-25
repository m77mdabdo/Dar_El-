<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\ShippingMethod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The admin UI for shipping_methods — the model/checkout integration were
 * already real and DB-backed (structured fee + delivery_time_min_days/max
 * + a code-based fallback, see ShippingMethod::DEFAULT_CODE and
 * ::ensureAtLeastOneActive()); only the admin screen to manage rows was
 * missing. Gated by the single flat 'shipping_settings.edit' permission
 * (matching Settings/Newsletter/Contact Messages' admin.permission
 * middleware convention) rather than a Policy — the permission model here
 * has no per-ability shipping_methods.view/create/edit/delete slugs to
 * back one, only the one slug already referenced by the sidebar entry
 * this page replaces.
 */
class ShippingMethodController extends Controller
{
    public function index(): View
    {
        $shippingMethods = ShippingMethod::orderBy('sort_order')->orderBy('id')->get();

        return view('admin.shipping-methods.index', compact('shippingMethods'));
    }

    public function create(): View
    {
        return view('admin.shipping-methods.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $shippingMethod = ShippingMethod::create($validated);

        ActivityLog::record('created', $shippingMethod, "Created shipping method {$shippingMethod->name_en}");

        return redirect()->route('admin.shipping-methods.index')->with('status', __('shipping_methods.created'));
    }

    public function edit(ShippingMethod $shippingMethod): View
    {
        return view('admin.shipping-methods.edit', compact('shippingMethod'));
    }

    public function update(Request $request, ShippingMethod $shippingMethod): RedirectResponse
    {
        $validated = $this->validated($request, $shippingMethod);

        $shippingMethod->update($validated);

        ActivityLog::record('updated', $shippingMethod, "Updated shipping method {$shippingMethod->name_en}");

        return redirect()->route('admin.shipping-methods.index')->with('status', __('shipping_methods.updated'));
    }

    /**
     * Deliberately no "in use by orders" block, unlike categories.destroy()
     * blocking on products()->count(): orders.shipping_method_id is
     * nullOnDelete, and every order already snapshots the method's name/
     * fee/delivery estimate onto its own row at checkout time
     * (shipping_method_code/name/shipping_delivery_min_days/max_days) —
     * the exact same reasoning as order_items.product_name. Deleting a
     * method that past orders reference doesn't change what those orders
     * display; nothing to guard against. If this leaves zero active
     * methods, ShippingMethod::ensureAtLeastOneActive() (already called
     * from CheckoutController::show()) recreates a safe default rather
     * than checkout ever breaking.
     */
    public function destroy(ShippingMethod $shippingMethod): RedirectResponse
    {
        $name = $shippingMethod->name_en;
        $shippingMethod->delete();

        ActivityLog::record('deleted', $shippingMethod, "Deleted shipping method {$name}");

        return redirect()->route('admin.shipping-methods.index')->with('status', __('shipping_methods.deleted'));
    }

    public function toggleActive(ShippingMethod $shippingMethod): RedirectResponse
    {
        $shippingMethod->update(['is_active' => ! $shippingMethod->is_active]);

        ActivityLog::record(
            $shippingMethod->is_active ? 'enabled' : 'disabled',
            $shippingMethod,
            "Toggled active status for shipping method {$shippingMethod->name_en}"
        );

        return back()->with('status', $shippingMethod->is_active
            ? __('shipping_methods.activated')
            : __('shipping_methods.deactivated'));
    }

    protected function validated(Request $request, ?ShippingMethod $shippingMethod = null): array
    {
        $request->merge(['is_active' => $request->boolean('is_active')]);

        $validated = $request->validate([
            'code' => ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique('shipping_methods', 'code')->ignore($shippingMethod?->id)],
            'name_ar' => ['required', 'string', 'max:255'],
            'name_en' => ['required', 'string', 'max:255'],
            'description_ar' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'fee' => ['required', 'integer', 'min:0'],
            'delivery_time_min_days' => ['required', 'integer', 'min:0'],
            'delivery_time_max_days' => ['required', 'integer', 'min:0', 'gte:delivery_time_min_days'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        // estimated_days is NOT NULL at the DB level and still read as a
        // fallback by deliveryEstimateLabel() when the structured min/max
        // columns are unset — this form only ever edits the structured
        // columns, so this keeps the legacy column in sync automatically
        // rather than asking an admin to fill in two representations of
        // the same range by hand.
        $validated['estimated_days'] = $validated['delivery_time_min_days'] === $validated['delivery_time_max_days']
            ? (string) $validated['delivery_time_min_days']
            : $validated['delivery_time_min_days'].'-'.$validated['delivery_time_max_days'];

        return $validated;
    }
}
