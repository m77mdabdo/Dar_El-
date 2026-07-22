<?php

namespace App\Http\Controllers;

use App\Exceptions\CheckoutRateLimitExceededException;
use App\Http\Requests\StoreCheckoutRequest;
use App\Jobs\GenerateAndSendInvoice;
use App\Mail\InvoiceMail;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\ProductSize;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Notifications\NewOrderPlaced;
use App\Notifications\OrderPlaced;
use App\Services\CartService;
use App\Services\CartTrackingService;
use App\Services\StockAlertService;
use App\Support\CheckoutAddressNormalizer;
use App\Support\PhoneNumberNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class CheckoutController extends Controller
{
    /** Guest-abuse guards — see the locked section in store() for how these are enforced atomically. */
    protected const MAX_ORDERS_PER_PHONE = 5;

    protected const MAX_ORDERS_PER_ADDRESS = 5;

    protected const RATE_LIMIT_WINDOW_MINUTES = 60;

    /** How long a resubmission of the exact same cart+phone reuses the original order instead of creating a duplicate. */
    protected const IDEMPOTENCY_WINDOW_MINUTES = 2;

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

        // Guarantees the shipping-method radio list below is never empty —
        // this is the fix for the "shipping method is required" dead end:
        // the page used to render zero options whenever the shipping_methods
        // table had no active rows, while validation still required one.
        ShippingMethod::ensureAtLeastOneActive();

        $captcha = auth()->check() ? null : $this->generateCaptcha();

        return view('checkout.show', [
            'items' => $this->cart->items(),
            'subtotal' => $this->cart->subtotal(),
            'discount' => $this->cart->discount(),
            'coupon' => $this->cart->appliedCoupon(),
            'shippingMethods' => ShippingMethod::where('is_active', true)->orderBy('fee')->get(),
            'heroImage' => Setting::get('checkout_hero_image', 'https://images.unsplash.com/photo-1772474569781-2fb1c6539f8c?w=1600&q=80&auto=format&fit=crop'),
            'hasStockIssues' => ! $this->cart->isValid(),
            'captchaA' => $captcha['a'] ?? null,
            'captchaB' => $captcha['b'] ?? null,
        ]);
    }

    /**
     * Small, home-grown math challenge rather than a third-party CAPTCHA
     * service — this store has no payment step to protect (COD only) and no
     * ad budget behind the traffic it worries about, just automated form
     * spam; a two-digit sum is enough friction for a script while staying
     * invisible to a real customer. The answer is session-stored, not
     * signed/embedded in the page, so it can't be read back out of the HTML
     * the way a hidden-field answer could be.
     */
    private function generateCaptcha(): array
    {
        $a = random_int(1, 9);
        $b = random_int(1, 9);

        session(['checkout_captcha_answer' => $a + $b]);

        return ['a' => $a, 'b' => $b];
    }

    public function store(StoreCheckoutRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $items = $this->cart->items();

        if (empty($items)) {
            Log::warning('Checkout blocked: cart empty', ['user_id' => $request->user()?->id]);

            return redirect()->route('cart.index')->with('error', __('Your cart is empty.'));
        }

        // Re-run the same self-heal as show() — store() is a separate
        // request and can't assume show() ran first (a bookmarked URL, a
        // resubmitted form, etc.), so the "standard" fallback in
        // StoreCheckoutRequest genuinely stays a last resort rather than
        // silently becoming the normal path.
        ShippingMethod::ensureAtLeastOneActive();

        $shippingMethod = $validated['shipping_method_id'] !== 'standard'
            ? ShippingMethod::find($validated['shipping_method_id'])
            : null;

        $shippingFee = $shippingMethod->fee ?? (int) Setting::get('default_shipping_fee', 0);

        // Recalculated from the database on every request — subtotal comes
        // from CartService (which reads live Product prices, never a
        // frontend-submitted value), and the shipping fee just resolved
        // above is a live ShippingMethod row, not anything the client sent.
        $subtotal = $this->cart->subtotal();
        $coupon = $this->cart->appliedCoupon();
        $discount = $this->cart->discount();
        $total = $this->cart->totalIncludingShipping($shippingFee);

        // Snapshots of the shipping method actually chosen — survive a later
        // edit/deactivation of the ShippingMethod row, same pattern as
        // order_items.product_name. The 'standard' string fallback has no
        // real row, so it snapshots the same literal defaults the checkout
        // page would have shown for it.
        $shippingMethodCode = $shippingMethod?->code ?? ShippingMethod::DEFAULT_CODE;
        $shippingMethodName = $shippingMethod ? trans_field($shippingMethod, 'name') : __('Standard Delivery');
        $shippingMinDays = $shippingMethod?->delivery_time_min_days ?? 3;
        $shippingMaxDays = $shippingMethod?->delivery_time_max_days ?? 5;

        $normalizedPhone = PhoneNumberNormalizer::normalize($validated['customer_phone']);
        $addressKey = CheckoutAddressNormalizer::key($validated['governorate'], $validated['city'], $validated['address']);

        // Cart contents + phone identify "this exact checkout attempt" —
        // used below to recognize a double-click/network-retry resubmission
        // before it creates a second order. Sorted so item order in the
        // cart array (which carries no meaning) can't produce two different
        // fingerprints for the same real cart.
        $cartSignature = collect($items)
            ->map(fn ($item) => $item['product']->id.':'.$item['size'].':'.$item['quantity'])
            ->sort()
            ->implode('|');
        $checkoutFingerprint = hash('sha256', $normalizedPhone.'|'.$cartSignature);

        $isDuplicateResubmission = false;

        try {
            // Both locks are always acquired in this same order (phone, then
            // address) at this single call site, so two requests can never
            // deadlock waiting on each other in opposite order. Each blocks
            // up to 5s then throws rather than hanging forever if something
            // is stuck holding a lock.
            $order = Cache::lock('checkout-lock:phone:'.$normalizedPhone, 15)->block(5, function () use (
                $addressKey, $checkoutFingerprint, $validated, $items, $shippingMethod, $shippingFee, $subtotal,
                $discount, $coupon, $total, $request, $shippingMethodCode, $shippingMethodName, $shippingMinDays,
                $shippingMaxDays, $normalizedPhone, &$isDuplicateResubmission,
            ) {
                return Cache::lock('checkout-lock:address:'.$addressKey, 15)->block(5, function () use (
                    $addressKey, $checkoutFingerprint, $validated, $items, $shippingMethod, $shippingFee, $subtotal,
                    $discount, $coupon, $total, $request, $shippingMethodCode, $shippingMethodName, $shippingMinDays,
                    $shippingMaxDays, $normalizedPhone, &$isDuplicateResubmission,
                ) {
                    // Idempotency check (Finding #3): a prior request from
                    // this exact phone+cart within the window already
                    // created an order — reuse it rather than double-submit.
                    // Safe to check before the rate limits below: reusing an
                    // existing order was never blocked by them the first
                    // time, so it can't be blocked by them now either.
                    $existing = Order::where('checkout_fingerprint', $checkoutFingerprint)
                        ->where('created_at', '>=', now()->subMinutes(self::IDEMPOTENCY_WINDOW_MINUTES))
                        ->latest('id')
                        ->first();

                    if ($existing) {
                        $isDuplicateResubmission = true;

                        return $existing;
                    }

                    $windowStart = now()->subMinutes(self::RATE_LIMIT_WINDOW_MINUTES);

                    $phoneCount = Order::where('customer_phone_normalized', $normalizedPhone)
                        ->where('created_at', '>=', $windowStart)
                        ->count();

                    if ($phoneCount >= self::MAX_ORDERS_PER_PHONE) {
                        throw new CheckoutRateLimitExceededException(
                            __('You\'ve placed several orders recently. Please wait a bit before placing another, or contact us directly if you need help.'),
                            'customer_phone'
                        );
                    }

                    $addressCount = Order::where('address_rate_limit_key', $addressKey)
                        ->where('created_at', '>=', $windowStart)
                        ->count();

                    if ($addressCount >= self::MAX_ORDERS_PER_ADDRESS) {
                        throw new CheckoutRateLimitExceededException(
                            __('Several orders were recently placed for this delivery address. Please wait a bit before placing another, or contact us directly if you need help.'),
                            'address'
                        );
                    }

                    return DB::transaction(function () use (
                        $validated, $items, $shippingMethod, $shippingFee, $subtotal, $discount, $coupon, $total, $request,
                        $shippingMethodCode, $shippingMethodName, $shippingMinDays, $shippingMaxDays,
                        $normalizedPhone, $addressKey, $checkoutFingerprint,
                    ) {
                        $order = Order::create([
                            'user_id' => $request->user()?->id,
                            'order_number' => 'ORD-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)),
                            'customer_name' => $validated['customer_name'],
                            // Empty string, not null — the column itself stays
                            // NOT NULL (no migration needed for what's ultimately
                            // just "no email provided"), and every consumer of this
                            // value already treats an empty string as "no email" the
                            // same way it would treat null: Order::resolveCustomerEmail()
                            // and maskEmailForLogging() both use falsy checks, not
                            // strict null checks.
                            'customer_email' => $validated['customer_email'] ?? '',
                            'customer_phone' => $validated['customer_phone'],
                            'customer_phone_normalized' => $normalizedPhone,
                            'governorate' => $validated['governorate'],
                            'city' => $validated['city'],
                            'address' => $validated['address'],
                            'address_rate_limit_key' => $addressKey,
                            'checkout_fingerprint' => $checkoutFingerprint,
                            'notes' => $validated['notes'] ?? null,
                            'locale' => app()->getLocale(),
                            'subtotal' => $subtotal,
                            'shipping_fee' => $shippingFee,
                            'coupon_code' => $coupon?->code,
                            'discount_amount' => $discount,
                            'shipping_method_id' => $shippingMethod?->id,
                            'shipping_method_code' => $shippingMethodCode,
                            'shipping_method_name' => $shippingMethodName,
                            'shipping_delivery_min_days' => $shippingMinDays,
                            'shipping_delivery_max_days' => $shippingMaxDays,
                            'total' => $total,
                            'status' => 'pending',
                            'payment_method' => $validated['payment_method'],
                            'payment_status' => Order::PAYMENT_STATUS_PENDING,
                            'customer_latitude' => $validated['customer_latitude'] ?? null,
                            'customer_longitude' => $validated['customer_longitude'] ?? null,
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
                });
            });
        } catch (CheckoutRateLimitExceededException $e) {
            Log::warning('Checkout blocked: rate limit exceeded', [
                'user_id' => $request->user()?->id,
                'field' => $e->field,
            ]);

            return back()->withErrors([$e->field => $e->getMessage()])->withInput();
        } catch (\RuntimeException $e) {
            // Stock-shortage messages are already specific and safe to show
            // the customer as-is (product name, size, remaining count).
            Log::warning('Checkout blocked: stock check failed', [
                'user_id' => $request->user()?->id,
                'message' => $e->getMessage(),
            ]);

            return back()->withErrors(['stock' => $e->getMessage()])->withInput();
        } catch (Throwable $e) {
            // Deliberately excludes anything from $validated beyond the
            // email (no password/OTP/payment fields exist on this form, but
            // this stays a scalar allowlist rather than dumping the array).
            Log::error('Checkout order creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'customer_email' => $validated['customer_email'] ?? null,
                'user_id' => $request->user()?->id,
            ]);

            return back()
                ->withErrors(['order' => __('Something went wrong while placing your order. Please try again.')])
                ->withInput();
        }

        if ($isDuplicateResubmission) {
            // The original submission already ran every step below (emails,
            // notifications, invoice job) — re-running them here would mean
            // the customer gets a second confirmation email/WhatsApp-style
            // notification for an order they only placed once. From the
            // customer's side this resubmission should look exactly like
            // the success it already was: same success page, cart cleared.
            Log::info('Checkout resubmission recognized: reused existing order instead of creating a duplicate', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
            ]);

            $this->cart->clear();

            return redirect()->route('checkout.success', $order)->with('status', __('Order placed successfully.'));
        }

        // Everything below only runs once the order is durably committed —
        // the cart is never cleared, and the customer is never bounced to
        // an error page, for a problem that happens after this point. Each
        // dispatch is isolated in its own try/catch (via dispatchSafely) so
        // a failure in one — e.g. an unresolvable customer email — can
        // never suppress the others, most importantly the admin
        // notification, which must always fire regardless of customer-side
        // outcomes.
        $customerEmail = $order->resolveCustomerEmail();

        if ($customerEmail) {
            // $invoice is intentionally omitted (null) here — this is the
            // immediate "order placed" confirmation, sent before any PDF
            // exists. GenerateAndSendInvoice sends the same Mailable again
            // with the real invoice once generation succeeds (see there).
            $this->dispatchSafely($order, InvoiceMail::class, function () use ($order, $customerEmail) {
                Mail::to($customerEmail)->locale($order->locale ?? app()->getLocale())->send(new InvoiceMail($order));
            }, [
                'recipient_resolved' => true,
                'recipient_masked' => Order::maskEmailForLogging($customerEmail),
            ]);
        } else {
            Log::warning('Order confirmation email skipped: no resolvable customer email', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'recipient_resolved' => false,
            ]);
        }

        // Database notification for the customer's own account — guests
        // have no notifiable account to attach one to, so they rely solely
        // on the email above. Deliberately a different notification class
        // from the admin one below (never the same class sent to both).
        if ($order->user_id) {
            $this->dispatchSafely($order, OrderPlaced::class, function () use ($order) {
                $order->user->notify(new OrderPlaced($order));
            });
        }

        $this->dispatchSafely($order, NewOrderPlaced::class, function () use ($order) {
            Notification::send(User::admins(), new NewOrderPlaced($order));
        });

        $this->dispatchSafely($order, GenerateAndSendInvoice::class, function () use ($order) {
            // ->afterCommit() is belt-and-suspenders here — this whole
            // block already runs after DB::transaction() above has
            // returned (and therefore committed), but stating it
            // explicitly means the guarantee survives even if a future
            // refactor moves this call somewhere less obviously safe.
            GenerateAndSendInvoice::dispatch($order)->afterCommit();
        }, [
            'queue_connection' => config('queue.default'),
        ]);

        if ($request->user()) {
            $this->cartTracking->markConverted($request->user(), $order);
        }

        $this->cart->clear();

        return redirect()->route('checkout.success', $order)->with('status', __('Order placed successfully.'));
    }

    public function success(Order $order): View
    {
        // Atomic compare-and-set: this UPDATE only affects a row where
        // purchase_event_fired_at is still null, so two concurrent requests
        // (or a refresh racing the first load) can't both "win" — exactly
        // one caller ever sees affected-rows === 1 and renders the purchase
        // tracking event. Same stock_deducted_at-style timestamp-guard
        // convention used elsewhere on this model, just single-query since
        // there's no related data to restore alongside it.
        $shouldTrackPurchase = Order::whereKey($order->id)
            ->whereNull('purchase_event_fired_at')
            ->update(['purchase_event_fired_at' => now()]) === 1;

        return view('checkout.success', compact('order', 'shouldTrackPurchase'));
    }

    /**
     * Runs one post-commit dispatch (a mail send, a notification, a queued
     * job) in isolation — a failure here is logged and swallowed rather
     * than propagated, so e.g. a bad customer email can never take down the
     * admin notification that runs right after it, and vice versa. Never
     * logs a full email address (see Order::maskEmailForLogging()).
     */
    private function dispatchSafely(Order $order, string $class, \Closure $action, array $context = []): void
    {
        $startedAt = microtime(true);

        try {
            $action();

            Log::info('Order post-commit dispatch succeeded', array_merge([
                'order_id' => $order->id,
                'class' => $class,
                'status' => 'success',
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ], $context));
        } catch (Throwable $e) {
            Log::error('Order post-commit dispatch failed (order already created successfully)', array_merge([
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'class' => $class,
                'error' => $e->getMessage(),
                'status' => 'failed',
                'duration_ms' => (int) ((microtime(true) - $startedAt) * 1000),
            ], $context));
        }
    }
}
