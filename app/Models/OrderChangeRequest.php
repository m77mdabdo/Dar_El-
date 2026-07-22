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

    protected function casts(): array
    {
        return [
            'order_item_ids' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
