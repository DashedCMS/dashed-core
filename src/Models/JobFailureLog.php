<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Dedup ledger for queue-job failures: one row per
 * (job_class, trace_hash, occurred_on date) tracks the count and last
 * message for that failure shape that day. The HandlesQueueFailures trait
 * upserts into this table from `failed()` so admins receive one alert
 * per shape per day instead of one per attempt.
 */
class JobFailureLog extends Model
{
    protected $table = 'dashed__job_failure_log';

    protected $fillable = [
        'job_class',
        'trace_hash',
        'occurred_on',
        'count',
        'last_message',
        'last_seen_at',
    ];

    protected $casts = [
        'occurred_on' => 'date',
        'last_seen_at' => 'datetime',
        'count' => 'integer',
    ];
}
