<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    // The one standardized payment method value — used consistently in the
    // checkout view, JS, StoreCheckoutRequest validation, this model, the
    // admin order view, order/invoice emails, and the invoice PDF. The
    // legacy 'cod' string is no longer written anywhere (a migration
    // backfilled existing rows).
    const PAYMENT_METHOD_COD = 'cash_on_delivery';

    const PAYMENT_STATUS_PENDING = 'pending';

    const PAYMENT_STATUS_PAID = 'paid';

    const PAYMENT_STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id', 'order_number', 'customer_name', 'customer_email', 'customer_phone',
        'governorate', 'city', 'address', 'notes', 'locale', 'subtotal', 'shipping_fee',
        'coupon_code', 'discount_amount', 'shipping_method_id', 'total', 'status',
        'payment_method', 'payment_status',
        'shipping_method_code', 'shipping_method_name',
        'shipping_delivery_min_days', 'shipping_delivery_max_days',
        'customer_latitude', 'customer_longitude',
        'stock_deducted_at', 'stock_restored_at', 'purchase_event_fired_at',
    ];

    protected function casts(): array
    {
        return [
            'stock_deducted_at' => 'datetime',
            'stock_restored_at' => 'datetime',
            'purchase_event_fired_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->latest('created_at');
    }

    /**
     * The single source of truth for "where do customer-facing order emails
     * go" — prefers the email snapshot taken at checkout time (correct for
     * both guests and logged-in customers, and immune to the account's
     * email changing later), falling back to the linked account only if
     * that snapshot is somehow empty. Never resolves to an admin/system
     * address, and returns null (never an invalid string) if nothing usable
     * is found so callers can safely skip sending instead of erroring.
     */
    public function resolveCustomerEmail(): ?string
    {
        $email = $this->customer_email ?: $this->user?->email;

        return $email && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    /**
     * Privacy-safe stand-in for logging a recipient — never write a full
     * email address to the logs, just enough to spot-check the right
     * domain/mailbox shape without exposing the address itself.
     */
    public static function maskEmailForLogging(?string $email): ?string
    {
        if (! $email || ! str_contains($email, '@')) {
            return null;
        }

        [$local, $domain] = explode('@', $email, 2);

        return mb_substr($local, 0, 1).'***@'.$domain;
    }

    /**
     * The real, forward-progress statuses an admin can set (see
     * Admin\OrderController::updateStatus()'s validation rule — the single
     * source of truth this list mirrors) minus 'cancelled', which is a
     * terminal branch off the line rather than a step on it. Order matters:
     * this IS the customer-facing tracker's step sequence.
     */
    public const TRACKING_STAGES = ['pending', 'processing', 'shipped', 'delivered'];

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * The real moment an admin set this order to 'delivered', from
     * statusHistories — not updated_at, which reflects the LAST change to
     * the row (an admin editing the shipping address after delivery, for
     * instance, would silently push the exchange-request deadline out).
     * Assumes statusHistories is already eager-loaded, same assumption as
     * trackingSteps(). Null if the order was never actually marked
     * delivered through the normal flow (e.g. seeded directly with that
     * status) — callers must treat that as "no exchange window," not fall
     * back to some other timestamp.
     */
    public function deliveredAt(): ?\Illuminate\Support\Carbon
    {
        return $this->statusHistories->firstWhere('status', 'delivered')?->created_at;
    }

    /**
     * One row per TRACKING_STAGES entry, in order, for rendering the
     * customer-facing tracker. A stage counts as reached/completed by its
     * position relative to the order's current status — not solely by
     * whether a matching statusHistories row exists — so a stage an admin
     * skipped over (e.g. jumping straight from pending to delivered) still
     * shows as completed rather than incorrectly greyed-out; it just has no
     * timestamp of its own in that case. Assumes statusHistories is already
     * eager-loaded (it always should be before calling this — see
     * OrderTrackingController/Account\OrderController::track()).
     *
     * @return array<int, array{key: string, completed: bool, current: bool, timestamp: ?\Illuminate\Support\Carbon}>
     */
    public function trackingSteps(): array
    {
        $currentIndex = array_search($this->status, self::TRACKING_STAGES, true);
        $currentIndex = $currentIndex === false ? -1 : $currentIndex;

        return collect(self::TRACKING_STAGES)
            ->map(fn (string $stage, int $index) => [
                'key' => $stage,
                'completed' => $index <= $currentIndex,
                'current' => $index === $currentIndex,
                'timestamp' => $this->statusHistories->firstWhere('status', $stage)?->created_at,
            ])
            ->values()
            ->all();
    }

    /**
     * 0–100, how far along TRACKING_STAGES the order's current status is —
     * feeds the connecting line's fill width in the tracker UI. Meaningless
     * (and never rendered) for a cancelled order.
     */
    public function trackingProgressPercent(): int
    {
        $currentIndex = array_search($this->status, self::TRACKING_STAGES, true);

        if ($currentIndex === false || $currentIndex <= 0) {
            return 0;
        }

        return (int) round(($currentIndex / (count(self::TRACKING_STAGES) - 1)) * 100);
    }
}
