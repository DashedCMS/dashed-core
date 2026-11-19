<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Dashed\DashedCore\Classes\Changes\ChangeSet;
use Dashed\DashedCore\Classes\Changes\ChangeFields;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Een wijziging die op een tijdstip wordt doorgevoerd en eventueel later
 * terugdraait. Uitvoeren en terugdraaien gebeurt in ScheduledChangeRunner.
 */
class ScheduledChange extends Model
{
    protected $table = 'dashed__scheduled_changes';

    public const STATUS_SCHEDULED = 'scheduled';
    public const STATUS_APPLIED = 'applied';
    public const STATUS_REVERTED = 'reverted';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REVERT_FAILED = 'revert_failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $guarded = [];

    protected $casts = [
        'changes' => 'array',
        'original' => 'array',
        'applied' => 'array',
        'skipped' => 'array',
        'run_at' => 'datetime',
        'revert_at' => 'datetime',
        'applied_at' => 'datetime',
        'reverted_at' => 'datetime',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model') ?: User::class);
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_SCHEDULED => __('Gepland'),
            self::STATUS_APPLIED => __('Uitgevoerd'),
            self::STATUS_REVERTED => __('Teruggedraaid'),
            self::STATUS_FAILED => __('Mislukt'),
            self::STATUS_REVERT_FAILED => __('Terugdraaien mislukt'),
            self::STATUS_CANCELLED => __('Geannuleerd'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusColors(): array
    {
        return [
            self::STATUS_SCHEDULED => 'info',
            self::STATUS_APPLIED => 'success',
            self::STATUS_REVERTED => 'gray',
            self::STATUS_FAILED => 'danger',
            self::STATUS_REVERT_FAILED => 'danger',
            self::STATUS_CANCELLED => 'gray',
        ];
    }

    public function scopeDue(Builder $query, $now = null): Builder
    {
        return $query->where('status', self::STATUS_SCHEDULED)->where('run_at', '<=', $now ?? now());
    }

    public function scopeDueForRevert(Builder $query, $now = null): Builder
    {
        return $query->where('status', self::STATUS_APPLIED)
            ->whereNotNull('revert_at')
            ->where('revert_at', '<=', $now ?? now());
    }

    public function resolveRecord(): ?Model
    {
        return ChangeFields::find($this->model_type, $this->model_id);
    }

    public function summary(): string
    {
        return ChangeSet::describe(ChangeFields::for($this->model_type), $this->changes ?? []);
    }

    public function waitsForRevert(): bool
    {
        return $this->status === self::STATUS_APPLIED && $this->revert_at !== null;
    }
}
