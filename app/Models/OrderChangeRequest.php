<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderChangeRequest extends Model
{
    public const TYPE_MODIFY = 'modify';

    public const TYPE_CANCEL = 'cancel';

    public const TYPE_EXCHANGE = 'exchange';

    public const TYPE_RETURN = 'return';

    /** Allowed while the order is still pending — see OrderChangeRequestController::resolveWindow(). */
    public const PENDING_WINDOW_TYPES = [self::TYPE_MODIFY, self::TYPE_CANCEL];

    /** Allowed within 3 days of the order being marked delivered. */
    public const DELIVERED_WINDOW_TYPES = [self::TYPE_EXCHANGE, self::TYPE_RETURN];

    public const TYPES = [self::TYPE_MODIFY, self::TYPE_CANCEL, self::TYPE_EXCHANGE, self::TYPE_RETURN];

    public const STATUS_PENDING = 'pending';

    public const STATUS_CONTACTED = 'contacted';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUSES = [self::STATUS_PENDING, self::STATUS_CONTACTED, self::STATUS_RESOLVED];

    public const REASONS = ['wrong_size', 'changed_mind', 'defective', 'wrong_item', 'other'];

    protected $fillable = [
        'order_id', 'type', 'order_item_ids', 'reason', 'notes', 'desired_variant', 'status',
    ];

    // Mirrors the migration's DB-level default, but at the PHP-object level
    // too: the saving() hook below reads $this->status to decide
    // pending_order_id, and that read happens before INSERT — before the
    // database's own column default would ever apply. Without this, a
    // freshly-created instance's status is simply unset (null) at hook time
    // whenever the caller (correctly) omits 'status' from create(), which
    // silently defeated the unique constraint below.
    protected $attributes = [
        'status' => self::STATUS_PENDING,
    ];

    protected function casts(): array
    {
        return [
            'order_item_ids' => 'array',
        ];
    }

    /**
     * Keeps pending_order_id mirroring order_id exactly while status is
     * pending, and NULL the instant it isn't — see the migration that added
     * this column for why (it's what makes the unique index only ever
     * collide between two pending rows for the same order). Deliberately
     * doesn't return anything: this is a "before save" halting event, and a
     * closure that returns false here would silently cancel the save.
     */
    protected static function booted(): void
    {
        static::saving(function (self $changeRequest) {
            $changeRequest->pending_order_id = $changeRequest->status === self::STATUS_PENDING
                ? $changeRequest->order_id
                : null;
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
