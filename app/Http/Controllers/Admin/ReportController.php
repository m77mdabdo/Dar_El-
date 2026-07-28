<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
     * defaulting to the last 30 days (inclusive) when not given.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function dateRange(Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))->startOfDay()
            : now()->subDays(29)->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))->endOfDay()
            : now()->endOfDay();

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

        $daily = Order::selectRaw("DATE(created_at) as day, COUNT(*) as orders_count, SUM(CASE WHEN status != 'cancelled' THEN total ELSE 0 END) as revenue")
            ->whereBetween('created_at', [$from, $to])
            ->groupBy('day')
            ->orderBy('day')
            ->get();

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
}
