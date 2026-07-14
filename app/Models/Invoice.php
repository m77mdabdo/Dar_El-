<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    const STATUS_PENDING = 'pending';

    const STATUS_PROCESSING = 'processing';

    const STATUS_GENERATED = 'generated';

    const STATUS_EMAILED = 'emailed';

    const STATUS_FAILED = 'failed';

    protected $fillable = [
        'order_id', 'invoice_number', 'pdf_path', 'issued_at',
        'status', 'failed_reason', 'emailed_at',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'emailed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The file itself, not $status, is the authority on whether a
     * download is actually servable — a failed *regeneration* attempt
     * still leaves a previously-generated, still-valid PDF in place.
     */
    public function isDownloadable(): bool
    {
        return (bool) $this->pdf_path && \Illuminate\Support\Facades\Storage::disk('local')->exists($this->pdf_path);
    }
}
