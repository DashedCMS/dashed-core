<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SentEmail extends Model
{
    public const STATUS_QUEUED = 'queued';
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_BOUNCED = 'bounced';
    public const STATUS_COMPLAINED = 'complained';
    public const STATUS_FAILED = 'failed';

    protected $table = 'dashed__sent_emails';

    protected $guarded = [];

    protected $casts = [
        'recipients' => 'array',
        'attachments' => 'array',
        'delivered_at' => 'datetime',
        'bounced_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'open_count' => 'integer',
        'click_count' => 'integer',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeOlderThan(Builder $query, Carbon $date): Builder
    {
        return $query->where('created_at', '<', $date);
    }
}
