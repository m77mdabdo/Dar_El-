<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Support\Facades\Storage;

/**
 * Public, signature-only invoice download — the link used in customer
 * emails. Unlike Account\OrderController::invoice() (auth + ownership
 * policy), this must also work for guest checkouts, which have no account
 * to log into at all. Security comes entirely from the URL's Laravel
 * signature (see the 'signed' middleware on its route) rather than from a
 * session, so this controller must never be reachable without one.
 */
class InvoiceDownloadController extends Controller
{
    public function show(Order $order)
    {
        $invoice = $order->invoice;

        abort_unless($invoice && $invoice->pdf_path && Storage::disk('local')->exists($invoice->pdf_path), 404);

        return Storage::disk('local')->download($invoice->pdf_path, "{$invoice->invoice_number}.pdf");
    }
}
