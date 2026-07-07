<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $data = Cache::remember('admin.dashboard.summary', 60, function () {
            return [
                'summary' => $this->summary(),
                'charts' => $this->charts(),
            ];
        });

        return view('admin.dashboard', $data + [
            'recentOrders' => Order::latest()->take(8)->get(),
            'recentCustomers' => User::whereHas('roles', fn ($q) => $q->where('name', 'customer'))->latest()->take(5)->get(),
            'recentMessages' => ContactMessage::latest()->take(5)->get(),
            'recentNotifications' => $request->user()->notifications()->latest()->take(5)->get(),
            'lowStockProducts' => $this->lowStockProducts(),
        ]);
    }

    protected function summary(): array
    {
        $stockCounts = Product::withSum('sizes as total_stock', 'stock')
            ->get(['id'])
            ->map(fn ($product) => (int) $product->total_stock);

        return [
            'total_orders' => Order::count(),
            'today_orders' => Order::whereDate('created_at', today())->count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'completed_orders' => Order::where('status', 'delivered')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),

            'total_revenue' => (int) Order::where('status', '!=', 'cancelled')->sum('total'),
            'today_revenue' => (int) Order::whereDate('created_at', today())->where('status', '!=', 'cancelled')->sum('total'),
            'monthly_revenue' => (int) Order::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->where('status', '!=', 'cancelled')->sum('total'),

            'total_customers' => User::whereHas('roles', fn ($q) => $q->where('name', 'customer'))->count(),
            'new_customers' => User::whereHas('roles', fn ($q) => $q->where('name', 'customer'))->where('created_at', '>=', now()->subDays(30))->count(),

            'products' => Product::count(),
            'categories' => Category::count(),
            'wishlist_items' => Wishlist::count(),
            'unread_messages' => ContactMessage::where('is_read', false)->count(),

            'low_stock_count' => $stockCounts->filter(fn ($s) => $s > 0 && $s <= Product::LOW_STOCK_THRESHOLD)->count(),
            'out_of_stock_count' => $stockCounts->filter(fn ($s) => $s <= 0)->count(),
        ];
    }

    protected function charts(): array
    {
        $since = now()->subDays(13)->startOfDay();

        $daily = Order::selectRaw("DATE(created_at) as day, COUNT(*) as orders_count, SUM(CASE WHEN status != 'cancelled' THEN total ELSE 0 END) as revenue")
            ->where('created_at', '>=', $since)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $labels = [];
        $ordersSeries = [];
        $revenueSeries = [];

        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('M j');
            $ordersSeries[] = (int) ($daily[$key]->orders_count ?? 0);
            $revenueSeries[] = (int) ($daily[$key]->revenue ?? 0);
        }

        $statusCounts = Order::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status');

        $topProducts = OrderItem::select('product_name', DB::raw('SUM(quantity) as qty'))
            ->groupBy('product_name')
            ->orderByDesc('qty')
            ->take(5)
            ->get();

        $topWishlist = Wishlist::select('product_id', DB::raw('COUNT(*) as count'))
            ->groupBy('product_id')
            ->orderByDesc('count')
            ->take(5)
            ->with('product:id,name_en,name_ar')
            ->get()
            ->filter(fn ($row) => $row->product !== null)
            ->values();

        return [
            'labels' => $labels,
            'orders_series' => $ordersSeries,
            'revenue_series' => $revenueSeries,
            'status_labels' => $statusCounts->keys()->map(fn ($s) => ucfirst($s))->all(),
            'status_series' => $statusCounts->values()->all(),
            'top_products_labels' => $topProducts->pluck('product_name')->all(),
            'top_products_series' => $topProducts->pluck('qty')->all(),
            'top_wishlist_labels' => $topWishlist->map(fn ($row) => trans_field($row->product, 'name'))->all(),
            'top_wishlist_series' => $topWishlist->pluck('count')->all(),
        ];
    }

    protected function lowStockProducts()
    {
        return Product::with('category')
            ->withSum('sizes as total_stock', 'stock')
            ->filterByStockStatus('low_stock')
            ->orderBy('total_stock')
            ->take(6)
            ->get();
    }
}
