<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Idempotency ledger for inbound webhooks. The
 * `EnsureWebhookIdempotency` middleware atomically inserts a row keyed on
 * `(provider, event_id)`, then mutates `status` as the request progresses.
 *
 * Duplicate webhooks short-circuit on the unique-index conflict.
 */
class WebhookLog extends Model
{
    public const STATUS_RECEIVED = 'received';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';

    protected $table = 'dashed__webhook_log';

    protected $fillable = [
        'provider',
        'event_id',
        'payload_hash',
        'site_id',
        'received_at',
        'processed_at',
        'status',
        'error',
        'payload',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function markProcessing(): void
    {
        $this->forceFill(['status' => self::STATUS_PROCESSING])->save();
    }

    public function markSucceeded(): void
    {
        $this->forceFill([
            'status' => self::STATUS_SUCCEEDED,
            'processed_at' => Carbon::now(),
            'error' => null,
        ])->save();
    }

    public function markFailed(?string $error = null): void
    {
        $this->forceFill([
            'status' => self::STATUS_FAILED,
            'processed_at' => Carbon::now(),
            'error' => $error,
        ])->save();
    }
}
