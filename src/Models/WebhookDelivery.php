<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    public const STATUS_PENDING = 'pending';
    /** Een job heeft de bezorging geclaimd en is bezig met versturen. */
    public const STATUS_SENDING = 'sending';
    public const STATUS_FAILED = 'failed';
    public const STATUS_SENT = 'sent';
    public const STATUS_ABANDONED = 'abandoned';

    protected $table = 'dashed__webhook_deliveries';

    protected $fillable = [
        'subscription_id', 'event', 'payload', 'attempt', 'status', 'response_code',
        'response_excerpt', 'error', 'next_attempt_at', 'sent_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_PENDING,
        'attempt' => 0,
    ];

    protected $casts = [
        'payload' => 'array',
        'attempt' => 'integer',
        'response_code' => 'integer',
        'next_attempt_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(WebhookSubscription::class, 'subscription_id');
    }
}
