<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Support\BusinessDay;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Deeper, filterable drill-downs — deliberately not a repeat of the
 * Dashboard's fixed-14-day charts (DashboardController::charts()), which
 * stay exactly as they are. Every method here is a real query scoped to
 * a caller-selected date range, gated by its own specific
 * 'reports.<name>' permission slug (already defined in
 * config/permission_groups.php and pre-wired in config/admin_sidebar.php
 * for exactly this purpose — finer-grained than the Dashboard's blanket
 * 'reports.view').
 */
class ReportController extends Controller
{
    /**
     * Shared by every report method: a caller-selected [from, to] range,
     * defaulting to the last 30 days (inclusive) when not given. Day
     * boundaries are anchored to Cairo calendar days (via BusinessDay),
     * not UTC ones — see BusinessDay's docblock for why.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function dateRange(Request $request): array
    {
        $from = $request->filled('from')
            ? BusinessDay::startOfDay($request->input('from'))
            : BusinessDay::startOfDay(BusinessDay::now()->subDays(29)->toDateString());

        $to = $request->filled('to')
            ? BusinessDay::endOfDay($request->input('to'))
            : BusinessDay::endOfDay();

        return [$from, $to];
    }

    /**
     * Revenue/order-count over the selected range, with a daily
     * breakdown for the on-screen trend — the full order-level detail
     * (not just daily aggregates) is what the CSV export contains
     * instead, since that's the actually useful drill-down artifact to
     * open in a spreadsheet, while the page itself stays a scannable
     * summary.
     */
    public function sales(Request $request): View
    {
        [$from, $to] = $this->dateRange($request);

        $ordersInRange = Order::whereBetween('created_at', [$from, $to]);
        $totalOrders = (clone $ordersInRange)->count();
        $completedOrders = (clone $ordersInRange)->where('status', '!=', 'cancelled')->count();
        $cancelledOrders = $totalOrders - $completedOrders;
        $totalRevenue = (int) (clone $ordersInRange)->where('status', '!=', 'cancelled')->sum('total');
        $averageOrderValue = $completedOrders > 0 ? round($totalRevenue / $completedOrders, 2) : 0;

        // Grouped in PHP rather than SQL's DATE(created_at) — that would
        // bucket by UTC calendar day, not Cairo. Doing the conversion in
        // SQL (e.g. CONVERT_TZ) would need MySQL's timezone tables loaded
        // on the server, which isn't guaranteed on shared hosting; PHP's
        // Carbon always has full, DST-aware tzdata regardless.
        $daily = Order::whereBetween('created_at', [$from, $to])
            ->get(['created_at', 'status', 'total'])
            ->groupBy(fn (Order $order) => $order->created_at->copy()->setTimezone(BusinessDay::TIMEZONE)->toDateString())
            ->map(fn ($orders, $day) => (object) [
                'day' => $day,
                'orders_count' => $orders->count(),
                'revenue' => $orders->where('status', '!=', 'cancelled')->sum('total'),
            ])
            ->sortBy('day')
            ->values();

        return view('admin.reports.sales', compact(
            'from', 'to', 'totalOrders', 'completedOrders', 'cancelledOrders', 'totalRevenue', 'averageOrderValue', 'daily'
        ));
    }

    public function salesExport(Request $request): StreamedResponse
    {
        [$from, $to] = $this->dateRange($request);

        return response()->streamDownload(function () use ($from, $to) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                __('reports.export_order_number'), __('reports.export_date'), __('reports.export_customer'),
                __('reports.export_status'), __('reports.export_total'),
            ]);

            Order::whereBetween('created_at', [$from, $to])
                ->orderBy('created_at')
                ->chunk(200, function ($orders) use ($handle) {
                    foreach ($orders as $order) {
                        fputcsv($handle, [
                            $order->order_number,
                            $order->created_at->toDateTimeString(),
                            $order->customer_name,
                            $order->status,
                            $order->total,
                        ]);
                    }
                });

            fclose($handle);
        }, 'sales-report-'.$from->toDateString().'-to-'.$to->toDateString().'.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * Best/worst sellers by quantity and by revenue over the selected
     * range. Grouped by product_id + the order item's own product_name
     * snapshot (same convention as OrderItem itself — a since-renamed or
     * deleted product still reads correctly here), non-cancelled orders
     * only. "Worst sellers" means the lowest quantity/revenue *among
     * products that sold at least once* in the range — not zero-sold
     * products, a different question this doesn't attempt to answer.
     */
    public function products(Request $request): View
    {
        [$from, $to] = $this->dateRange($request);

        $baseQuery = fn () => OrderItem::query()
            ->select('product_id', 'product_name', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(quantity * price) as total_revenue'))
            ->whereHas('order', fn ($q) => $q->whereBetween('created_at', [$from, $to])->where('status', '!=', 'cancelled'))
            ->groupBy('product_id', 'product_name');

        $topByQuantity = $baseQuery()->orderByDesc('total_quantity')->take(10)->get();
        $topByRevenue = $baseQuery()->orderByDesc('total_revenue')->take(10)->get();
        $worstByQuantity = $baseQuery()->orderBy('total_quantity')->take(10)->get();

        return view('admin.reports.products', compact('from', 'to', 'topByQuantity', 'topByRevenue', 'worstByQuantity'));
    }

    /**
     * Top customers by order count / total spend over the selected range,
     * plus a new-vs-returning breakdown. Guest checkout doesn't exist in
     * this app (CheckoutController always attaches the authenticated
     * user's id), so grouping by user_id is safe — every non-cancelled
     * order in range has one. "Returning" means the customer already had
     * at least one order (of any status) before the range started; "new"
     * means their first-ever order falls inside the range. Cancelled
     * orders are excluded from the ranking tables (same convention as
     * Reports > Products) but NOT from the returning-customer lookback,
     * since a cancelled order still proves the person was already a
     * customer before this range.
     */
    public function customers(Request $request): View
    {
        [$from, $to] = $this->dateRange($request);

        $baseQuery = fn () => Order::query()
            ->select('user_id', DB::raw('COUNT(*) as orders_count'), DB::raw('SUM(total) as total_spent'))
            ->whereNotNull('user_id')
            ->whereBetween('created_at', [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->groupBy('user_id')
            ->with('user:id,name,email');

        $topByOrderCount = $baseQuery()->orderByDesc('orders_count')->take(10)->get();
        $topBySpend = $baseQuery()->orderByDesc('total_spent')->take(10)->get();

        $customerIdsInRange = Order::query()
            ->whereNotNull('user_id')
            ->whereBetween('created_at', [$from, $to])
            ->where('status', '!=', 'cancelled')
            ->distinct()
            ->pluck('user_id');

        $returningCustomers = $customerIdsInRange->isEmpty() ? 0 : Order::query()
            ->whereIn('user_id', $customerIdsInRange)
            ->where('created_at', '<', $from)
            ->distinct('user_id')
            ->count('user_id');

        $totalCustomers = $customerIdsInRange->count();
        $newCustomers = $totalCustomers - $returningCustomers;

        return view('admin.reports.customers', compact(
            'from', 'to', 'topByOrderCount', 'topBySpend', 'totalCustomers', 'newCustomers', 'returningCustomers'
        ));
    }

    /**
     * Stock valuation (quantity x price per product) and a low-stock
     * summary — not a date-ranged report like the other four (stock is a
     * point-in-time snapshot, not something that happened "between two
     * dates"). Deliberately reuses Product::filterByStockStatus() (the
     * exact same scope InventoryController::index() uses) rather than
     * re-deriving low/out-of-stock logic here, and the same
     * category/withSum('sizes as total_stock') shape as that page, so
     * this is a valuation/ranking view over the same data rather than a
     * second, drifting definition of "low stock".
     */
    public function inventory(Request $request): View
    {
        $stockExpr = '(select coalesce(sum(stock), 0) from product_sizes where product_sizes.product_id = products.id)';

        $products = Product::query()
            ->with('category:id,name_ar,name_en')
            ->withSum('sizes as total_stock', 'stock')
            ->addSelect(DB::raw("({$stockExpr}) * products.price as stock_value"))
            ->filterByStockStatus($request->stock_status)
            ->when(
                $request->sort === 'stock_asc',
                fn ($q) => $q->orderBy('total_stock'),
                fn ($q) => $request->sort === 'value_asc'
                    ? $q->orderBy('stock_value')
                    : $q->orderByDesc('stock_value')
            )
            ->paginate(20)
            ->withQueryString();

        $totalValuation = (int) Product::query()->selectRaw("SUM(({$stockExpr}) * products.price) as total")->value('total');
        $lowStockCount = Product::query()->filterByStockStatus('low_stock')->count();
        $outOfStockCount = Product::query()->filterByStockStatus('out_of_stock')->count();

        return view('admin.reports.inventory', compact('products', 'totalValuation', 'lowStockCount', 'outOfStockCount'));
    }
}
