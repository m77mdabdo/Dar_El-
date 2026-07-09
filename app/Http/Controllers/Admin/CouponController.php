<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', Coupon::class);

        $coupons = Coupon::latest()->paginate(20);

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        $this->authorize('create', Coupon::class);

        return view('admin.coupons.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Coupon::class);

        $coupon = Coupon::create($this->validated($request));

        ActivityLog::record('created', $coupon, "Created coupon {$coupon->code}");

        return redirect()->route('admin.coupons.index')->with('status', __('coupons.created'));
    }

    public function edit(Coupon $coupon)
    {
        $this->authorize('update', $coupon);

        return view('admin.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon): RedirectResponse
    {
        $this->authorize('update', $coupon);

        $coupon->update($this->validated($request, $coupon));

        ActivityLog::record('updated', $coupon, "Updated coupon {$coupon->code}");

        return redirect()->route('admin.coupons.index')->with('status', __('coupons.updated'));
    }

    public function destroy(Coupon $coupon): RedirectResponse
    {
        $this->authorize('delete', $coupon);

        $code = $coupon->code;
        $coupon->delete();

        ActivityLog::record('deleted', $coupon, "Deleted coupon {$code}");

        return redirect()->route('admin.coupons.index')->with('status', __('coupons.deleted'));
    }

    protected function validated(Request $request, ?Coupon $coupon = null): array
    {
        $request->merge(['is_active' => $request->boolean('is_active')]);

        return $request->validate([
            'code' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique(Coupon::class)->ignore($coupon)],
            'type' => ['required', 'in:percentage,fixed'],
            'value' => ['required', 'integer', 'min:0'],
            'min_order_amount' => ['nullable', 'integer', 'min:0'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['boolean'],
        ]);
    }
}
