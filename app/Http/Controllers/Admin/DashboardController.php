<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartReminder;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\OrderChangeRequest;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Models\Wishlist;
use App\Support\BusinessDay;
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
                'needsAttention' => $this->needsAttention(),
                'recentOrders' => Order::latest()->take(8)->get(),
                'recentCustomers' => User::whereHas('roles', fn ($q) => $q->where('name', 'customer'))->latest()->take(5)->get(),
                'recentMessages' => ContactMessage::latest()->take(5)->get(),
                'lowStockProducts' => $this->lowStockProducts(),
            ];
        });

        // Every item above is computed unconditionally and shared across
        // all admin users via the one cache key — role-based visibility
        // can't live inside the cached closure (the first user to hit a
        // cache miss would decide what every other role sees for the next
        // 60s). So filtering by the CURRENT request's permissions happens
        // here instead, every request, cheaply (hasAdminAccess() only
        // touches the already-loaded permission relation).
        $user = $request->user();
        $data['needsAttention'] = collect($data['needsAttention'])
            ->filter(fn (array $item) => $user->hasAdminAccess($item['permission']))
            ->values()
            ->all();

        $canViewOrders = $user->hasAdminAccess('orders.view');
        $canViewCustomers = $user->hasAdminAccess('customers.view');
        $canViewMessages = $user->hasAdminAccess('messages.view');
        $canViewInventory = $user->hasAdminAccess('inventory.view');

        // Belt-and-suspenders alongside the @if guards already in
        // admin/dashboard.blade.php: emptying the underlying collection
        // here means a future accidentally-deleted @if can't leak this
        // data across roles — the view would just render nothing instead
        // of silently exposing it, matching how needsAttention is already
        // filtered above rather than left to the view alone.
        if (! $canViewOrders) {
            $data['recentOrders'] = collect();
        }
        if (! $canViewCustomers) {
            $data['recentCustomers'] = collect();
        }
        if (! $canViewMessages) {
            $data['recentMessages'] = collect();
        }
        if (! $canViewInventory) {
            $data['lowStockProducts'] = collect();
        }

        return view('admin.dashboard', $data + [
            // Per-admin-user data — must stay outside the shared cache key
            // above, or the first admin to hit a cache miss would have
            // their own notifications served to every other admin for the
            // next 60s.
            'recentNotifications' => $request->user()->notifications()->latest()->take(5)->get(),

            // Role-based simplification: the dense stat-card/chart block is
            // real BI/analytics territory, not something a minimally-
            // permissioned employee needs — gated behind the one
            // reports-related permission that already exists for exactly
            // this purpose. Each Recent Activity panel is gated on its own
            // narrower, operational permission instead (an inventory-only
            // employee should still see the low-stock list even without
            // reports.view) rather than tying every panel to the same
            // all-or-nothing analytics gate.
            'showAnalytics' => $user->hasAdminAccess('reports.view'),
            'canViewOrders' => $canViewOrders,
            'canViewCustomers' => $canViewCustomers,
            'canViewMessages' => $canViewMessages,
            'canViewInventory' => $canViewInventory,
        ]);
    }

    /**
     * The "what needs my attention today" list — every item an admin
     * screen already exposes some form of, just never surfaced together
     * anywhere: pending orders, pending order-change/return requests
     * (built earlier and, per the admin usability audit, had zero
     * dashboard presence until now), combined low+out-of-stock alerts
     * (reusing Product::filterByStockStatus() — the exact scope the
     * Inventory page itself filters on), unread contact messages, and
     * reviews awaiting moderation. Computed unconditionally (cheap
     * counts) and filtered down to what the current user may see in
     * index() above, not here.
     */
    protected function needsAttention(): array
    {
        $lowStockCount = Product::filterByStockStatus('low_stock')->count();
        $outOfStockCount = Product::filterByStockStatus('out_of_stock')->count();

        return [
            [
                'permission' => 'orders.view',
                'label' => __('admin.dashboard.attention_pending_orders'),
                'value' => Order::where('status', 'pending')->count(),
                'href' => route('admin.orders.index', ['status' => 'pending']),
                'icon' => 'cart',
            ],
            [
                'permission' => 'orders.view',
                'label' => __('admin.dashboard.attention_change_requests'),
                'value' => OrderChangeRequest::pending()->count(),
                'href' => route('admin.order-change-requests.index'),
                'icon' => 'exchange',
            ],
            [
                'permission' => 'inventory.view',
                'label' => __('admin.dashboard.attention_stock_alerts'),
                'value' => $lowStockCount + $outOfStockCount,
                'sublabel' => __('admin.dashboard.attention_stock_alerts_detail', ['low' => $lowStockCount, 'out' => $outOfStockCount]),
                'href' => route('admin.inventory.index', ['sort' => 'stock_asc']),
                'icon' => 'warning',
            ],
            [
                'permission' => 'messages.view',
                'label' => __('admin.dashboard.attention_unread_messages'),
                'value' => ContactMessage::where('is_read', false)->count(),
                'href' => route('admin.contact-messages.index'),
                'icon' => 'envelope',
            ],
            [
                'permission' => 'reviews.view',
                'label' => __('admin.dashboard.attention_pending_reviews'),
                'value' => Review::pending()->count(),
                'href' => route('admin.reviews.index', ['status' => 'pending']),
                'icon' => 'star',
            ],
        ];
    }

    protected function summary(): array
    {
        $stockCounts = Product::withSum('sizes as total_stock', 'stock')
            ->get(['id'])
            ->map(fn ($product) => (int) $product->total_stock);

        // "Today" per the Cairo business calendar, not UTC — see
        // BusinessDay's docblock. Every "today" stat below shares this one
        // computed range rather than each calling today()/whereDate()
        // separately.
        $todayRange = BusinessDay::todayRange();

        return [
            'total_orders' => Order::count(),
            'today_orders' => Order::whereBetween('created_at', $todayRange)->count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'completed_orders' => Order::where('status', 'delivered')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),

            'total_revenue' => (int) Order::where('status', '!=', 'cancelled')->sum('total'),
            'today_revenue' => (int) Order::whereBetween('created_at', $todayRange)->where('status', '!=', 'cancelled')->sum('total'),
            'monthly_revenue' => (int) Order::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->where('status', '!=', 'cancelled')->sum('total'),

            'total_customers' => User::whereHas('roles', fn ($q) => $q->where('name', 'customer'))->count(),
            'new_customers' => User::whereHas('roles', fn ($q) => $q->where('name', 'customer'))->where('created_at', '>=', now()->subDays(30))->count(),

            'products' => Product::count(),
            'categories' => Category::count(),
            'wishlist_items' => Wishlist::count(),
            'unread_messages' => ContactMessage::where('is_read', false)->count(),

            'low_stock_count' => $stockCounts->filter(fn ($s) => $s > 0 && $s <= Product::LOW_STOCK_THRESHOLD)->count(),
            'out_of_stock_count' => $stockCounts->filter(fn ($s) => $s <= 0)->count(),

            'new_customers_today' => User::whereHas('roles', fn ($q) => $q->where('name', 'customer'))->whereBetween('created_at', $todayRange)->count(),

            'active_carts_count' => Cart::where('status', 'active')->count(),
            'abandoned_carts_count' => Cart::where('status', 'abandoned')->count(),
            'converted_carts_count' => Cart::where('status', 'converted')->count(),
            'cart_conversion_rate' => $this->cartConversionRate(),
            'abandoned_cart_value' => (int) Cart::where('status', 'abandoned')->sum('total'),
            'reminders_sent_today' => CartReminder::whereBetween('created_at', $todayRange)->where('status', 'sent')->count(),
        ];
    }

    protected function cartConversionRate(): float
    {
        $converted = Cart::where('status', 'converted')->count();
        $totalClosed = $converted + Cart::whereIn('status', ['abandoned', 'expired'])->count();

        return $totalClosed > 0 ? round($converted / $totalClosed * 100, 1) : 0;
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

        $dailyCustomers = User::whereHas('roles', fn ($q) => $q->where('name', 'customer'))
            ->select(DB::raw('DATE(created_at) as day'), DB::raw('COUNT(*) as count'))
            ->where('created_at', '>=', $since)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $dailyAbandoned = Cart::select(DB::raw('DATE(updated_at) as day'), DB::raw('COUNT(*) as count'))
            ->where('status', 'abandoned')
            ->where('updated_at', '>=', $since)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $dailyConverted = Cart::select(DB::raw('DATE(converted_at) as day'), DB::raw('COUNT(*) as count'))
            ->whereNotNull('converted_at')
            ->where('converted_at', '>=', $since)
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->keyBy('day');

        $newCustomersSeries = [];
        $abandonedCartsSeries = [];
        $cartConversionSeries = [];

        for ($i = 13; $i >= 0; $i--) {
            $key = now()->subDays($i)->format('Y-m-d');
            $newCustomersSeries[] = (int) ($dailyCustomers[$key]->count ?? 0);
            $abandonedCartsSeries[] = (int) ($dailyAbandoned[$key]->count ?? 0);
            $cartConversionSeries[] = (int) ($dailyConverted[$key]->count ?? 0);
        }

        $topCartProducts = CartItem::select('product_id', DB::raw('SUM(quantity) as qty'))
            ->whereHas('cart', fn ($q) => $q->whereIn('status', ['active', 'abandoned']))
            ->groupBy('product_id')
            ->orderByDesc('qty')
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

            'new_customers_labels' => $labels,
            'new_customers_series' => $newCustomersSeries,
            'abandoned_carts_labels' => $labels,
            'abandoned_carts_series' => $abandonedCartsSeries,
            'cart_conversion_labels' => $labels,
            'cart_conversion_series' => $cartConversionSeries,
            'top_cart_products_labels' => $topCartProducts->map(fn ($row) => trans_field($row->product, 'name'))->all(),
            'top_cart_products_series' => $topCartProducts->pluck('qty')->all(),
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
