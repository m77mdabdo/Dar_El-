<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = $request->user()->orders()->latest()->paginate(10);

        return view('account.orders.index', compact('orders'));
    }

    public function show(Request $request, Order $order)
    {
        $this->authorize('view', $order);

        $order->load(['items.product', 'statusHistories', 'shippingMethod']);

        return view('account.orders.show', compact('order'));
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

        if (! $invoice || ! $invoice->pdf_path || ! Storage::disk('local')->exists($invoice->pdf_path)) {
            // The order itself is real (route model binding already
            // guaranteed that) — only the invoice isn't ready yet, e.g. its
            // generation is still queued or previously failed. A bare 404
            // here reads as a broken link; send the visitor back to the
            // order they were just looking at with an explanation instead.
            $showRoute = $request->route()->getName() === 'admin.orders.invoice' ? 'admin.orders.show' : 'account.orders.show';

            return redirect()->route($showRoute, $order)->with('error', __('orders.invoice_not_ready'));
        }

        return Storage::disk('local')->download($invoice->pdf_path, "{$invoice->invoice_number}.pdf");
    }
}
