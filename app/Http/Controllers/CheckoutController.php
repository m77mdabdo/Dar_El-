<?php

namespace App\Http\Controllers;

use App\Jobs\GenerateAndSendInvoice;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\ProductSize;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Notifications\NewOrderPlaced;
use App\Services\CartService;
use App\Services\CartTrackingService;
use App\Services\StockAlertService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cart,
        protected StockAlertService $stockAlerts,
        protected CartTrackingService $cartTracking,
    ) {}

    public function show(): View|RedirectResponse
    {
        if (empty($this->cart->content())) {
            return redirect()->route('cart.index');
        }

        return view('checkout.show', [
            'items' => $this->cart->items(),
            'subtotal' => $this->cart->subtotal(),
            'discount' => $this->cart->discount(),
            'coupon' => $this->cart->appliedCoupon(),
            'shippingMethods' => ShippingMethod::where('is_active', true)->get(),
            'heroImage' => Setting::get('checkout_hero_image', 'https://images.unsplash.com/photo-1772474569781-2fb1c6539f8c?w=1600&q=80&auto=format&fit=crop'),
            'hasStockIssues' => ! $this->cart->isValid(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:30'],
            'governorate' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'notes' => ['nullable', 'string'],
            'shipping_method_id' => ['required', 'exists:shipping_methods,id'],
        ]);

        $items = $this->cart->items();

        if (empty($items)) {
            return redirect()->route('cart.index')->with('error', 'Your cart is empty.');
        }

        $shippingMethod = ShippingMethod::findOrFail($validated['shipping_method_id']);
        $subtotal = $this->cart->subtotal();
        $coupon = $this->cart->appliedCoupon();
        $discount = $this->cart->discount();
        $total = max(0, $subtotal + $shippingMethod->fee - $discount);

        try {
            $order = DB::transaction(function () use ($validated, $items, $shippingMethod, $subtotal, $discount, $coupon, $total, $request) {
                $order = Order::create([
                    'user_id' => $request->user()?->id,
                    'order_number' => 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                    'customer_name' => $validated['customer_name'],
                    'customer_email' => $validated['customer_email'],
                    'customer_phone' => $validated['customer_phone'],
                    'governorate' => $validated['governorate'],
                    'city' => $validated['city'],
                    'address' => $validated['address'],
                    'notes' => $validated['notes'] ?? null,
                    'subtotal' => $subtotal,
                    'shipping_fee' => $shippingMethod->fee,
                    'coupon_code' => $coupon?->code,
                    'discount_amount' => $discount,
                    'shipping_method_id' => $shippingMethod->id,
                    'total' => $total,
                    'status' => 'pending',
                    'payment_method' => 'cod',
                ]);

                foreach ($items as $item) {
                    // Lock the row so two customers racing for the last piece
                    // can't both succeed; the second one re-checks fresh stock.
                    $productSize = ProductSize::where('product_id', $item['product']->id)
                        ->where('size', $item['size'])
                        ->lockForUpdate()
                        ->first();

                    $before = $productSize?->stock ?? 0;

                    if (! $productSize || $before < $item['quantity']) {
                        throw new \RuntimeException(__('Sorry, ":name" (size :size) only has :count piece(s) left. Please update your cart.', [
                            'name' => trans_field($item['product'], 'name'),
                            'size' => $item['size'],
                            'count' => $before,
                        ]));
                    }

                    $order->items()->create([
                        'product_id' => $item['product']->id,
                        'product_name' => $item['product']->name_en,
                        'size' => $item['size'],
                        'price' => $item['product']->price,
                        'quantity' => $item['quantity'],
                    ]);

                    $productSize->decrement('stock', $item['quantity']);

                    $this->stockAlerts->checkThreshold($item['product'], $productSize, $before, $before - $item['quantity']);
                }

                if ($coupon) {
                    $coupon->increment('used_count');
                }

                OrderStatusHistory::create([
                    'order_id' => $order->id,
                    'status' => 'pending',
                    'note' => 'Order placed.',
                    'changed_by' => $request->user()?->id,
                ]);

                $order->forceFill(['stock_deducted_at' => now()])->save();

                return $order;
            });
        } catch (\RuntimeException $e) {
            return back()->withErrors(['stock' => $e->getMessage()])->withInput();
        }

        GenerateAndSendInvoice::dispatch($order);
        Notification::send(User::admins(), new NewOrderPlaced($order));

        if ($request->user()) {
            $this->cartTracking->markConverted($request->user(), $order);
        }

        $this->cart->clear();

        return redirect()->route('checkout.success', $order)->with('status', 'Order placed successfully.');
    }

    public function success(Order $order): View
    {
        return view('checkout.success', compact('order'));
    }
}
