<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderChangeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderChangeRequestController extends Controller
{
    /**
     * Lightweight visibility only — no filtering/search UI, matching the
     * simplicity of ContactMessageController/NewsletterController. order.user
     * is eager-loaded for the customer-name column (a guest order has none,
     * so the view falls back to the order's own customer_name snapshot).
     */
    public function index()
    {
        $requests = OrderChangeRequest::with('order.user')->latest()->paginate(20);

        return view('admin.order-change-requests.index', compact('requests'));
    }

    /**
     * A single action for both "mark contacted" and "mark resolved" (a
     * status dropdown per row, not two separate buttons) — this doesn't
     * need its own full CRUD, just enough to track that someone followed
     * up, matching ContactMessageController::markRead()'s scope exactly.
     */
    public function updateStatus(Request $request, OrderChangeRequest $orderChangeRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(OrderChangeRequest::STATUSES)],
        ]);

        $orderChangeRequest->update(['status' => $validated['status']]);

        return back()->with('status', __('order_change_requests.status_updated'));
    }
}
