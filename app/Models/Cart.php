<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cart extends Model
{
    protected $fillable = [
        'user_id', 'status', 'subtotal', 'total', 'items_count',
        'last_activity_at', 'last_reminder_sent_at', 'reminder_count',
        'converted_at', 'order_id', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'last_activity_at' => 'datetime',
            'last_reminder_sent_at' => 'datetime',
            'converted_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function reminders(): HasMany
    {
        return $this->hasMany(CartReminder::class)->latest();
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['active', 'abandoned']);
    }

    public function scopeAbandoned($query)
    {
        return $query->where('status', 'abandoned');
    }

    public function scopeEligibleForReminder($query)
    {
        return $query->abandoned()
            ->where('items_count', '>', 0)
            ->where('reminder_count', '<', config('cart.max_reminders'))
            ->where(function ($q) {
                $q->whereNull('last_reminder_sent_at')
                    ->orWhere('last_reminder_sent_at', '<=', now()->subHours(config('cart.reminder_interval_hours')));
            });
    }

    public function abandonedDuration(): ?string
    {
        if ($this->status !== 'abandoned') {
            return null;
        }

        return $this->last_activity_at->diffForHumans(now(), true);
    }
}
