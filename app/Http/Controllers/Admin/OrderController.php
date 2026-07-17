<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\ProductSize;
use App\Models\User;
use App\Notifications\OrderCancelled;
use App\Notifications\OrderStatusUpdated;
use App\Services\BackInStockService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class OrderController extends Controller
{
    public function __construct(protected BackInStockService $backInStock)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Order::class);

        $orders = Order::with('payment')
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->payment_status, fn ($q) => $q->whereHas('payment', fn ($p) => $p->where('status', $request->payment_status)))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $this->authorize('view', $order);

        $order->load(['items', 'statusHistories.changedBy', 'payment', 'shippingMethod', 'user']);

        return view('admin.orders.show', compact('order'));
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        $this->authorize('updateStatus', $order);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,processing,shipped,delivered,cancelled'],
            'note' => ['nullable', 'string'],
        ]);

        // Status change + history + stock restoration are one atomic unit —
        // either the whole cancellation succeeds together, or none of it
        // does. Before this, each step committed independently: a failure
        // partway through (e.g. restoreStock() failing) left the order
        // permanently showing "cancelled" in its status/history/activity
        // log while the stock was silently never returned to inventory,
        // with nothing telling the admin that had happened.
        //
        // Notifications are deliberately NOT inside this transaction and
        // run afterward in their own try/catch — a notification failure
        // must never roll back a legitimate, already-decided status change
        // (same lesson as StockAlertService/CartTrackingService).
        $restoredCount = DB::transaction(function () use ($request, $order, $validated) {
            $order->update(['status' => $validated['status']]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $validated['status'],
                'note' => $validated['note'] ?? null,
                'changed_by' => $request->user()->id,
            ]);

            ActivityLog::record('updated', $order, "Updated order {$order->order_number} status to {$validated['status']}");

            $restoredCount = null;

            if ($validated['status'] === 'cancelled') {
                // $order here was fetched via route-model-binding before
                // this transaction started, so it can be stale: a
                // concurrent cancellation request for the same order may
                // have been running this same transaction and already
                // restored stock in the moment between that fetch and now.
                // Lock the row and re-read stock_restored_at fresh — a
                // second request's lockForUpdate() blocks until the first
                // commits, then sees the true, already-restored state,
                // instead of trusting a stale copy and double-crediting
                // inventory.
                $lockedOrder = Order::whereKey($order->id)->lockForUpdate()->first();

                if ($lockedOrder->stock_deducted_at && ! $lockedOrder->stock_restored_at) {
                    $restoredCount = $this->restoreStock($lockedOrder);
                }
            }

            return $restoredCount;
        });

        if ($order->user) {
            try {
                $order->user->notify(new OrderStatusUpdated($order));
            } catch (Throwable $e) {
                Log::error('Order status notification failed (status change still proceeds)', [
                    'order_id' => $order->id,
                    'status' => $validated['status'],
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($validated['status'] === 'cancelled') {
            try {
                Notification::send(User::admins(), new OrderCancelled($order));
            } catch (Throwable $e) {
                Log::error('Order cancelled admin notification failed (status change still proceeds)', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('status', $restoredCount !== null
            ? __('orders.status_updated_with_restock', ['count' => $restoredCount])
            : __('orders.status_updated'));
    }

    /**
     * Return an order's items to stock. Guarded by stock_restored_at so a
     * cancellation can never be "double restored" (e.g. re-saving the same
     * status, or a retried request).
     */
    protected function restoreStock(Order $order): int
    {
        return DB::transaction(function () use ($order) {
            $order->load('items');
            $restored = 0;

            foreach ($order->items as $item) {
                if (! $item->product_id) {
                    continue;
                }

                $productSize = ProductSize::with('product')
                    ->where('product_id', $item->product_id)
                    ->where('size', $item->size)
                    ->lockForUpdate()
                    ->first();

                if ($productSize) {
                    $before = $productSize->stock;
                    $productSize->increment('stock', $item->quantity);
                    $restored++;

                    if ($productSize->product) {
                        $this->backInStock->checkAndNotify($productSize->product, $productSize, $before, $before + $item->quantity);
                    }
                }
            }

            $order->forceFill(['stock_restored_at' => now()])->save();

            return $restored;
        });
    }
}
