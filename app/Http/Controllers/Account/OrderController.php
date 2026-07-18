<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        // items.product feeds each order card's thumbnail preview.
        $orders = $request->user()->orders()->with('items.product')->latest()->paginate(10);

        return view('account.orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order)
    {
        $this->authorize('view', $order);

        $order->load(['items.product', 'statusHistories', 'shippingMethod']);

        return view('account.orders.show', compact('order'));
    }

    /**
     * Same ownership check (and same underlying OrderPolicy::view — an
     * admin browsing here also passes via before()) as show() above, just
     * rendering the visual tracker instead of the plain detail page.
     */
    public function track(Order $order)
    {
        $this->authorize('view', $order);

        $order->load(['items.product', 'statusHistories']);

        return view('orders.track', ['order' => $order, 'isGuest' => false]);
    }

    /**
     * Shared by both the customer route (account.orders.invoice) and the
     * admin route (admin.orders.invoice) — same policy-gated logic either
     * way (an admin always passes OrderPolicy::before()).
     */
    public function invoice(Request $request, Order $order)
    {
        $this->authorize('view', $order);

        $invoice = $order->invoice;

        if (! $invoice || ! $invoice->isDownloadable()) {
            // The order itself is real (route model binding already
            // guaranteed that) — only the invoice isn't ready yet. A bare
            // 404 here reads as a broken link; send the visitor back to
            // the order they were just looking at with an explanation
            // instead. "failed" gets a distinct message from "still
            // queued/processing" — previously both cases showed the same
            // "still preparing" text forever, with no way for the
            // customer (or support) to tell a permanent failure apart
            // from normal in-progress generation.
            $showRoute = $request->route()->getName() === 'admin.orders.invoice' ? 'admin.orders.show' : 'account.orders.show';

            $message = $invoice?->status === Invoice::STATUS_FAILED
                ? __('orders.invoice_generation_failed')
                : __('orders.invoice_not_ready');

            return redirect()->route($showRoute, $order)->with('error', $message);
        }

        return Storage::disk('local')->download($invoice->pdf_path, "{$invoice->invoice_number}.pdf");
    }
}
