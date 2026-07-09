<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendAbandonedCartReminderJob;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\CartReminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $carts = Cart::with(['user', 'items'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->date_from, fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->when($request->search, fn ($q) => $q->whereHas('user', fn ($u) => $u
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
                ->orWhere('phone', 'like', "%{$request->search}%")
            ))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $stats = $this->stats();
        $charts = $this->charts();

        return view('admin.carts.index', compact('carts', 'stats', 'charts'));
    }

    public function show(Cart $cart)
    {
        $cart->load(['user', 'items.product', 'reminders.user', 'order']);

        return view('admin.carts.show', compact('cart'));
    }

    public function sendReminder(Cart $cart): RedirectResponse
    {
        Log::info('Admin requested cart reminder', ['cart_id' => $cart->id, 'customer_email' => $cart->user?->email]);

        if ($cart->items_count === 0 || $cart->status === 'converted') {
            return back()->with('error', __('carts.cannot_remind'));
        }

        if (! $cart->user || ! $cart->user->email) {
            Log::warning('Admin cart reminder blocked: customer has no email', ['cart_id' => $cart->id]);

            return back()->with('error', __('carts.no_customer_email'));
        }

        $cart->update(['status' => 'abandoned']);

        if ($this->dispatchReminderAndCheckSuccess($cart)) {
            return back()->with('status', __('carts.reminder_sent'));
        }

        return back()->with('error', __('carts.reminder_failed'));
    }

    public function bulkReminder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cart_ids' => ['required', 'array'],
            'cart_ids.*' => ['integer'],
        ]);

        $carts = Cart::whereIn('id', $validated['cart_ids'])
            ->where('items_count', '>', 0)
            ->where('status', '!=', 'converted')
            ->with('user')
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($carts as $cart) {
            Log::info('Admin requested bulk cart reminder', ['cart_id' => $cart->id, 'customer_email' => $cart->user?->email]);

            if (! $cart->user || ! $cart->user->email) {
                Log::warning('Bulk cart reminder skipped: customer has no email', ['cart_id' => $cart->id]);
                $failed++;

                continue;
            }

            $cart->update(['status' => 'abandoned']);

            if ($this->dispatchReminderAndCheckSuccess($cart)) {
                $sent++;
            } else {
                $failed++;
            }
        }

        if ($failed > 0 && $sent === 0) {
            return back()->with('error', __('carts.reminder_failed'));
        }

        $message = __('carts.bulk_reminders_sent', ['count' => $sent]);

        if ($failed > 0) {
            $message .= ' '.__('carts.bulk_reminders_failed', ['count' => $failed]);
        }

        return back()->with('status', $message);
    }

    /**
     * Dispatch the reminder synchronously (not queued) so this admin-triggered
     * action returns an accurate, immediate result to the button instead of
     * silently depending on a queue worker being run.
     */
    protected function dispatchReminderAndCheckSuccess(Cart $cart): bool
    {
        $reminderCountBefore = $cart->reminder_count;

        SendAbandonedCartReminderJob::dispatchSync($cart, force: true);

        $cart->refresh();

        $succeeded = $cart->reminder_count > $reminderCountBefore;

        if ($succeeded) {
            Log::info('Admin cart reminder sent successfully', ['cart_id' => $cart->id, 'customer_email' => $cart->user->email]);
        } else {
            $lastError = $cart->reminders()->first()?->error_message;
            Log::error('Admin cart reminder failed to send', ['cart_id' => $cart->id, 'error' => $lastError]);
        }

        return $succeeded;
    }

    protected function stats(): array
    {
        $active = Cart::where('status', 'active')->count();
        $abandoned = Cart::where('status', 'abandoned')->count();
        $converted = Cart::where('status', 'converted')->count();
        $expired = Cart::where('status', 'expired')->count();
        $totalClosed = $converted + $abandoned + $expired;

        return [
            'active' => $active,
            'abandoned' => $abandoned,
            'converted' => $converted,
            'expired' => $expired,
            'conversion_rate' => $totalClosed > 0 ? round($converted / $totalClosed * 100, 1) : 0,
            'abandoned_value' => (int) Cart::where('status', 'abandoned')->sum('total'),
            'reminders_sent_today' => CartReminder::whereDate('created_at', today())->where('status', 'sent')->count(),
        ];
    }

    protected function charts(): array
    {
        $byStatus = Cart::select('status', DB::raw('COUNT(*) as count'))->groupBy('status')->pluck('count', 'status');

        $since = now()->subDays(13)->startOfDay();

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

        $labels = [];
        $abandonedSeries = [];
        $convertedSeries = [];

        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $key = $date->format('Y-m-d');
            $labels[] = $date->format('M j');
            $abandonedSeries[] = (int) ($dailyAbandoned[$key]->count ?? 0);
            $convertedSeries[] = (int) ($dailyConverted[$key]->count ?? 0);
        }

        $topProducts = CartItem::select('product_id', DB::raw('SUM(quantity) as qty'))
            ->whereHas('cart', fn ($q) => $q->open())
            ->groupBy('product_id')
            ->orderByDesc('qty')
            ->take(5)
            ->with('product:id,name_en,name_ar')
            ->get()
            ->filter(fn ($row) => $row->product !== null)
            ->values();

        return [
            'byStatus' => $byStatus,
            'labels' => $labels,
            'abandonedSeries' => $abandonedSeries,
            'convertedSeries' => $convertedSeries,
            'topProducts' => $topProducts,
        ];
    }
}
