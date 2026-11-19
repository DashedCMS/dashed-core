<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\Classes\Changes\ChangeSet;
use Dashed\DashedCore\Classes\Changes\ChangeFields;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eén bulkbewerking: welke bewerkingen op welke records, en per record wat
 * er veranderde (BulkEditItem), zodat hij ongedaan te maken is.
 */
class BulkEditRun extends Model
{
    protected $table = 'dashed__bulk_edit_runs';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_UNDOING = 'undoing';
    public const STATUS_UNDONE = 'undone';

    protected $guarded = [];

    protected $casts = [
        'changes' => 'array',
        'record_ids' => 'array',
        'total' => 'integer',
        'done' => 'integer',
        'changed' => 'integer',
        'failed' => 'integer',
        'undo_skipped' => 'integer',
        'undone_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(BulkEditItem::class);
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
            self::STATUS_QUEUED => __('In de wachtrij'),
            self::STATUS_RUNNING => __('Bezig'),
            self::STATUS_COMPLETED => __('Uitgevoerd'),
            self::STATUS_FAILED => __('Mislukt'),
            self::STATUS_UNDOING => __('Wordt ongedaan gemaakt'),
            self::STATUS_UNDONE => __('Ongedaan gemaakt'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusColors(): array
    {
        return [
            self::STATUS_QUEUED => 'gray',
            self::STATUS_RUNNING => 'info',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
            self::STATUS_UNDOING => 'warning',
            self::STATUS_UNDONE => 'gray',
        ];
    }

    public function summary(): string
    {
        return ChangeSet::describe(ChangeFields::for($this->model_type), $this->changes ?? []);
    }

    public function canBeUndone(): bool
    {
        return $this->status === self::STATUS_COMPLETED && $this->changed > 0;
    }
}
