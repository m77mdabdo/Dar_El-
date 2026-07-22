<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderChangeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class OrderTrackingController extends Controller
{
    public function form(): View
    {
        return view('orders.track-form');
    }

    /**
     * Order number + a second verification field (email or phone,
     * whichever the customer remembers — both are always captured at
     * checkout, so either matching is a genuine identity check on the
     * order). A single query combining both conditions means there's
     * nothing here that could distinguish "wrong order number" from
     * "wrong contact info" for the caller — the not-found branch below is
     * the same regardless of which part was actually wrong, so a brute
     * -force attempt can't learn which valid order numbers exist.
     */
    public function lookup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order_number' => ['required', 'string', 'max:50'],
            'contact' => ['required', 'string', 'max:255'],
        ], [], [
            'order_number' => __('orders.track_order_number'),
            'contact' => __('orders.track_contact'),
        ]);

        $order = Order::where('order_number', $validated['order_number'])
            ->where(function ($query) use ($validated) {
                $query->where('customer_email', $validated['contact'])
                    ->orWhere('customer_phone', $validated['contact']);
            })
            ->first();

        if (! $order) {
            // Not back() — a direct POST (no prior GET to the form in this
            // request cycle, e.g. a test, or a bookmarked/retried submit)
            // has no referer to fall back to, which would otherwise send a
            // guest to the homepage instead of back to the form with their
            // error.
            return redirect()->route('track-order.form')
                ->withInput(['order_number' => $validated['order_number']])
                ->with('error', __('orders.track_not_found'));
        }

        // Temporary (not permanent) signature, same 90-day expiry as the
        // invoice-download link (resources/views/emails/orders/confirmation.blade.php)
        // — this page shows the customer's shipping address and phone, so an
        // indefinitely-valid link would leak that PII forever if forwarded,
        // screenshotted, or left in browser history.
        return redirect(URL::temporarySignedRoute('track-order.show', now()->addDays(90), ['order' => $order->id]));
    }

    /**
     * Guest-safe: no auth, security comes entirely from the URL signature
     * minted in lookup() above (same convention as invoice.download) —
     * only reachable after a successful order-number + contact-info match.
     */
    public function show(Order $order): View
    {
        $order->load(['items.product.images', 'statusHistories']);

        // Its own freshly-minted signed URL, distinct from this page's own
        // signature — Laravel signatures are tied to one specific route +
        // params, so the guest-safe change-request POST needs its own
        // (same 90-day expiry as this page's own link and invoice.download,
        // for the same reason: this exposes shipping address/phone-adjacent
        // action, not something to stay valid forever if the link leaks).
        $changeRequestActionUrl = URL::temporarySignedRoute('order-change-requests.store', now()->addDays(90), ['order' => $order->id]);
        $existingChangeRequest = OrderChangeRequest::where('order_id', $order->id)
            ->where('status', OrderChangeRequest::STATUS_PENDING)
            ->first();

        return view('orders.track', [
            'order' => $order, 'isGuest' => true,
            'changeRequestActionUrl' => $changeRequestActionUrl, 'existingChangeRequest' => $existingChangeRequest,
        ]);
    }
}
