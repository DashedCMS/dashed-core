<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Een herinnering of taak, aan elk record te hangen (polymorf) of los. Op
 * `remind_at` stuurt ReminderSender een melding naar de bel van `user_id`,
 * een push naar de app (via het event ReminderDue, waar dashed-mobile-api op
 * luistert) en, als de ontvanger dat wil, een mail.
 */
class Reminder extends Model
{
    protected $table = 'dashed__reminders';

    protected $guarded = [];

    protected $casts = [
        'remind_at' => 'datetime',
        'sent_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function remindable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Het gebruikersmodel van het project, zie BackgroundTask::user().
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model') ?: User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model') ?: User::class, 'created_by');
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    public function scopeDue(Builder $query): Builder
    {
        return $query->open()->whereNull('sent_at')->where('remind_at', '<=', now());
    }

    public function scopeFor(Builder $query, Model $record): Builder
    {
        return $query->where('remindable_type', $record->getMorphClass())
            ->where('remindable_id', (string) $record->getKey());
    }

    public function isOverdue(): bool
    {
        return ! $this->completed_at && $this->remind_at->isPast() && ! $this->remind_at->isToday();
    }

    public function complete(): void
    {
        $this->update(['completed_at' => now()]);
    }

    /**
     * Uitstellen zet de melding opnieuw klaar: zonder `sent_at` terug op leeg
     * zou een uitgestelde herinnering nooit meer afgaan.
     */
    public function snooze(\DateTimeInterface $until): void
    {
        $this->update(['remind_at' => $until, 'sent_at' => null]);
    }
}
