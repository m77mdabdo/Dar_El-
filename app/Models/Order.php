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
        'stock_deducted_at', 'stock_restored_at',
    ];

    protected function casts(): array
    {
        return [
            'stock_deducted_at' => 'datetime',
            'stock_restored_at' => 'datetime',
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
}
