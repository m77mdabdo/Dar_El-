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

        $order->load(['items', 'statusHistories', 'shippingMethod']);

        return view('account.orders.show', compact('order'));
    }

    public function invoice(Request $request, Order $order)
    {
        $this->authorize('view', $order);

        $invoice = $order->invoice;

        abort_unless($invoice && $invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path), 404);

        return Storage::disk('local')->download($invoice->pdf_path, "{$invoice->invoice_number}.pdf");
    }
}
