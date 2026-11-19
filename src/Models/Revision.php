<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Een volledige momentopname van een CMS-record (vertaalbare velden, content,
 * metadata en extra blokken) zoals hij in de database stond.
 *
 * Statussen:
 * - published / approved: een versie die live heeft gestaan
 * - draft / pending: een open concept van de goedkeuringsstroom (hooguit één per record)
 * - superseded: een concept dat is ingehaald door een directe publicatie
 * - discarded: een concept dat is weggegooid
 */
class Revision extends Model
{
    public const STATUS_PUBLISHED = 'published';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING = 'pending';

    public const STATUS_SUPERSEDED = 'superseded';

    public const STATUS_DISCARDED = 'discarded';

    public const LIVE_STATUSES = [self::STATUS_PUBLISHED, self::STATUS_APPROVED];

    public const OPEN_STATUSES = [self::STATUS_DRAFT, self::STATUS_PENDING];

    protected $table = 'dashed__revisions';

    protected $guarded = [];

    protected $casts = [
        'snapshot' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function revisionable(): ?Model
    {
        $class = $this->revisionable_type;

        if (! class_exists($class)) {
            return null;
        }

        $query = $class::query()->withoutGlobalScopes();

        return $query->whereKey($this->revisionable_id)->first();
    }

    public function scopeForRecord(Builder $query, Model $record): Builder
    {
        return $query->where('revisionable_type', $record::class)
            ->where('revisionable_id', (string) $record->getKey());
    }

    public function scopeLive(Builder $query): Builder
    {
        return $query->whereIn('status', self::LIVE_STATUSES);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function isLive(): bool
    {
        return in_array($this->status, self::LIVE_STATUSES, true);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_PUBLISHED => __('Gepubliceerd'),
            self::STATUS_APPROVED => __('Goedgekeurd'),
            self::STATUS_DRAFT => __('Concept'),
            self::STATUS_PENDING => __('Ter beoordeling'),
            self::STATUS_SUPERSEDED => __('Vervangen'),
            self::STATUS_DISCARDED => __('Verworpen'),
            default => (string) $this->status,
        };
    }

    public function reasonLabel(): ?string
    {
        return match ($this->reason) {
            'baseline' => __('Stand vóór de eerste bewerking'),
            'save' => __('Opgeslagen'),
            'restore' => __('Teruggezet'),
            'draft' => __('Concept opgeslagen'),
            default => null,
        };
    }

    public static function userLabel(?User $user): string
    {
        if (! $user) {
            return __('Systeem');
        }

        $name = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));

        return $name !== '' ? $name : (string) $user->email;
    }
}
