<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendAbandonedCartReminderJob;
use App\Models\ActivityLog;
use App\Models\CustomerNote;
use App\Models\User;
use App\Notifications\LoginAlertNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->hasAdminAccess('customers.view'), 403);

        $customers = User::whereHas('roles', fn ($q) => $q->where('name', 'customer'))
            ->withCount('orders', 'wishlists')
            ->withSum(['orders as total_spent' => fn ($q) => $q->where('status', '!=', 'cancelled')], 'total')
            ->with(['carts' => fn ($q) => $q->open()->latest()->limit(1)])
            ->when($request->verified === '1', fn ($q) => $q->whereNotNull('email_verified_at'))
            ->when($request->verified === '0', fn ($q) => $q->whereNull('email_verified_at'))
            ->when($request->has_orders === '1', fn ($q) => $q->has('orders'))
            ->when($request->has_abandoned_cart === '1', fn ($q) => $q->whereHas('carts', fn ($c) => $c->abandoned()))
            ->when($request->has_wishlist === '1', fn ($q) => $q->has('wishlists'))
            ->when($request->new === '1', fn ($q) => $q->where('created_at', '>=', now()->subDays(7)))
            ->when($request->date_from, fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->date_to, fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->when($request->spent_min, fn ($q) => $q->having('total_spent', '>=', (int) $request->spent_min))
            ->when($request->spent_max, fn ($q) => $q->having('total_spent', '<=', (int) $request->spent_max))
            ->when($request->search, fn ($q) => $q->where(fn ($sub) => $sub
                ->where('name', 'like', "%{$request->search}%")
                ->orWhere('email', 'like', "%{$request->search}%")
                ->orWhere('phone', 'like', "%{$request->search}%")
            ))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.customers.index', compact('customers'));
    }

    public function show(User $customer)
    {
        abort_unless(auth()->user()->hasAdminAccess('customers.view'), 403);
        abort_unless($customer->hasRole('customer'), 404);

        $customer->load(['customerNotes' => fn ($q) => $q->with('admin')->latest()]);

        $stats = [
            'total_orders' => $customer->orders()->count(),
            'completed_orders' => $customer->orders()->where('status', 'delivered')->count(),
            'cancelled_orders' => $customer->orders()->where('status', 'cancelled')->count(),
            'pending_orders' => $customer->orders()->whereIn('status', ['pending', 'processing', 'shipped'])->count(),
            'total_spent' => (int) $customer->orders()->where('status', '!=', 'cancelled')->sum('total'),
            'wishlist_count' => $customer->wishlists()->count(),
        ];
        $stats['average_order_value'] = $stats['total_orders'] > 0 ? (int) round($stats['total_spent'] / $stats['total_orders']) : 0;

        $recentOrders = $customer->orders()->latest()->take(8)->get();
        $currentCart = $customer->carts()->open()->with('items')->latest()->first();
        $stats['cart_items_count'] = $currentCart?->items_count ?? 0;
        $wishlist = $customer->wishlists()->with('product')->latest()->take(8)->get();
        $loginHistory = $customer->notifications()
            ->where('type', LoginAlertNotification::class)
            ->latest()
            ->take(8)
            ->get();

        return view('admin.customers.show', compact('customer', 'stats', 'recentOrders', 'currentCart', 'wishlist', 'loginHistory'));
    }

    public function orders(User $customer)
    {
        abort_unless(auth()->user()->hasAdminAccess('customers.view'), 403);
        abort_unless($customer->hasRole('customer'), 404);

        $orders = $customer->orders()->latest()->paginate(20);

        return view('admin.customers.orders', compact('customer', 'orders'));
    }

    public function carts(User $customer)
    {
        abort_unless(auth()->user()->hasAdminAccess('customers.carts_view'), 403);
        abort_unless($customer->hasRole('customer'), 404);

        $carts = $customer->carts()->with('items')->latest()->paginate(20);

        return view('admin.customers.carts', compact('customer', 'carts'));
    }

    public function wishlist(User $customer)
    {
        abort_unless(auth()->user()->hasAdminAccess('customers.wishlist_view'), 403);
        abort_unless($customer->hasRole('customer'), 404);

        $wishlist = $customer->wishlists()->with('product.sizes')->latest()->paginate(20);

        return view('admin.customers.wishlist', compact('customer', 'wishlist'));
    }

    public function addNote(Request $request, User $customer): RedirectResponse
    {
        abort_unless($request->user()->hasAdminAccess('customers.notes'), 403);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
        ]);

        CustomerNote::create([
            'user_id' => $customer->id,
            'admin_id' => $request->user()->id,
            'note' => $validated['note'],
        ]);

        return back()->with('status', __('customers.note_added'));
    }

    public function toggleDisabled(Request $request, User $customer): RedirectResponse
    {
        abort_unless($request->user()->hasAdminAccess('customers.disable'), 403);
        abort_if($customer->id === $request->user()->id || $customer->hasAnyRole(['admin', 'super_admin']), 403);

        $customer->forceFill(['disabled_at' => $customer->isDisabled() ? null : now()])->save();

        ActivityLog::record($customer->isDisabled() ? 'disabled' : 'enabled', $customer, "Toggled disabled status for {$customer->name}");

        return back()->with('status', $customer->isDisabled() ? __('customers.customer_disabled') : __('customers.customer_enabled'));
    }

    public function sendReminder(User $customer): RedirectResponse
    {
        abort_unless(auth()->user()->hasAdminAccess('customers.send_reminder'), 403);

        Log::info('Admin requested cart reminder', ['customer_id' => $customer->id, 'customer_email' => $customer->email]);

        $cart = $customer->carts()->open()->where('items_count', '>', 0)->latest()->first();

        if (! $cart) {
            return back()->with('error', __('customers.no_cart_to_remind'));
        }

        if (! $customer->email) {
            Log::warning('Admin cart reminder blocked: customer has no email', ['customer_id' => $customer->id]);

            return back()->with('error', __('customers.no_customer_email'));
        }

        // A manual admin-triggered reminder bypasses the "must already be
        // abandoned" gate and reminder cap the automated command enforces,
        // but still goes through the same job so it's logged/counted
        // identically. Dispatched synchronously (not queued) so this
        // interactive action returns an accurate result immediately instead
        // of silently depending on a queue worker being run.
        $cart->update(['status' => 'abandoned']);
        $reminderCountBefore = $cart->reminder_count;

        SendAbandonedCartReminderJob::dispatchSync($cart, force: true);

        $cart->refresh();

        if ($cart->reminder_count > $reminderCountBefore) {
            Log::info('Admin cart reminder sent successfully', ['customer_id' => $customer->id, 'customer_email' => $customer->email]);

            return back()->with('status', __('customers.reminder_sent'));
        }

        $lastError = $cart->reminders()->first()?->error_message;
        Log::error('Admin cart reminder failed to send', ['customer_id' => $customer->id, 'error' => $lastError]);

        return back()->with('error', __('customers.reminder_failed'));
    }

    public function destroy(User $customer): RedirectResponse
    {
        abort_unless(auth()->user()->hasAdminAccess('customers.delete'), 403);
        abort_if($customer->hasAnyRole(['admin', 'super_admin']), 403);

        if ($customer->orders()->count() > 0) {
            return back()->with('error', __('customers.cannot_delete_has_orders'));
        }

        $name = $customer->name;
        $customer->delete();

        ActivityLog::record('deleted', $customer, "Deleted customer {$name}");

        return redirect()->route('admin.customers.index')->with('status', __('customers.customer_deleted'));
    }
}
