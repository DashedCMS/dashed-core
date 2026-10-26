<?php

namespace Dashed\DashedCore\Models;

use Throwable;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Een taak op de achtergrond, voor het takenicoon in de topbalk. Een job
 * meldt zich met begin(), werkt de voortgang bij met progress() of advance()
 * en sluit af met finish() of fail(). Bestaande voortgangsmodellen (Export,
 * AutomatedTranslationProgress) blijven bestaan en spiegelen zich hierin via
 * mirror(), op hun eigen subject.
 *
 * Alles loopt door rescue(): de administratie van een taak mag het werk
 * zelf nooit tegenhouden.
 */
class BackgroundTask extends Model
{
    protected $table = 'dashed__background_tasks';

    public const STATUS_QUEUED = 'queued';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Een lopende taak zonder enige update in deze tijd is vastgelopen: de
     * worker is gestopt of de job is hard afgebroken zonder failed().
     */
    public const STALE_AFTER_MINUTES = 120;

    protected $guarded = [];

    protected $casts = [
        'done' => 'integer',
        'total' => 'integer',
        'notify' => 'boolean',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /**
     * Het gebruikersmodel van het project (App\Models\User in een
     * klantproject), anders landt een melding in de bel onder een ander
     * notifiable_type dan waaronder de bel hem zoekt.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model') ?: User::class);
    }

    public static function begin(string $type, string $label, ?int $total = null, Authenticatable|int|null $user = null, bool $notify = true): self
    {
        return rescue(fn () => static::query()->create([
            'type' => $type,
            'label' => $label,
            'user_id' => $user instanceof Authenticatable ? $user->getAuthIdentifier() : $user,
            'status' => self::STATUS_RUNNING,
            'done' => 0,
            'total' => $total,
            'notify' => $notify,
            'started_at' => now(),
        ]), fn () => new static(['type' => $type, 'label' => $label]), report: true);
    }

    /**
     * Zoekt of maakt de taak die bij een bestaand voortgangsrecord hoort en
     * zet de velden die meegegeven zijn. Een taak die al klaar was, wordt
     * niet opnieuw gemeld.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function mirror(Model $subject, array $attributes): ?self
    {
        return rescue(function () use ($subject, $attributes) {
            $task = static::query()->firstOrNew([
                'subject_type' => $subject->getMorphClass(),
                'subject_id' => (string) $subject->getKey(),
            ]);

            $wasFinished = $task->exists && $task->isFinished();
            $task->fill($attributes);
            $task->started_at ??= now();

            if ($task->isFinished()) {
                $task->finished_at ??= now();
            }

            $task->save();

            if (! $wasFinished && $task->isFinished()) {
                $task->sendNotification();
            }

            return $task;
        }, null, report: true);
    }

    public function progress(int $done, ?int $total = null): self
    {
        return $this->persist(array_filter([
            'done' => $done,
            'total' => $total,
            'status' => self::STATUS_RUNNING,
        ], fn ($value) => $value !== null));
    }

    public function advance(int $by = 1): self
    {
        return $this->progress(($this->done ?? 0) + $by);
    }

    public function finish(?string $message = null, ?string $url = null, ?string $urlLabel = null): self
    {
        $this->persist([
            'status' => self::STATUS_COMPLETED,
            'done' => $this->total ?? $this->done ?? 0,
            'message' => $message,
            'result_url' => $url,
            'result_label' => $urlLabel,
            'finished_at' => now(),
        ]);
        $this->sendNotification();

        return $this;
    }

    public function fail(string|Throwable|null $error = null): self
    {
        $this->persist([
            'status' => self::STATUS_FAILED,
            'message' => $error instanceof Throwable ? $error->getMessage() : $error,
            'finished_at' => now(),
        ]);
        $this->sendNotification();

        return $this;
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }

    public function percentage(): ?int
    {
        if (! $this->total) {
            return $this->status === self::STATUS_COMPLETED ? 100 : null;
        }

        return (int) min(100, floor($this->done / $this->total * 100));
    }

    /**
     * Resterende tijd uit het tempo tot nu toe, in seconden. Null zolang er
     * nog niets klaar is of het totaal onbekend is.
     */
    public function secondsRemaining(?Carbon $now = null): ?int
    {
        if ($this->status !== self::STATUS_RUNNING || ! $this->total || $this->done < 1 || ! $this->started_at) {
            return null;
        }

        $elapsed = max(1, $this->started_at->diffInSeconds($now ?? now()));

        return (int) round($elapsed / $this->done * max(0, $this->total - $this->done));
    }

    /**
     * Wat de ingelogde gebruiker te zien krijgt: zijn eigen taken en die
     * zonder starter (vertalingen, een nieuwsbrief, de opruimronde: die
     * draaien in de wachtrij en kennen geen gebruiker), en voor een
     * superadmin ook de taken van anderen.
     */
    public function scopeVisibleTo(Builder $query, ?Authenticatable $user): Builder
    {
        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if (($user->role ?? null) === 'superadmin') {
            return $query;
        }

        return $query->where(fn (Builder $query) => $query
            ->where('user_id', $user->getAuthIdentifier())
            ->orWhereNull('user_id'));
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_QUEUED, self::STATUS_RUNNING]);
    }

    /**
     * Zet taken die al STALE_AFTER_MINUTES niets meer van zich lieten horen
     * op mislukt, zodat ze niet eeuwig als lopend in de topbalk staan.
     */
    public static function markStale(): int
    {
        $stale = static::query()
            ->active()
            ->where('updated_at', '<', now()->subMinutes(self::STALE_AFTER_MINUTES))
            ->get();

        foreach ($stale as $task) {
            $task->fail(__('Geen voortgang meer gemeld; de taak is waarschijnlijk afgebroken.'));
        }

        return $stale->count();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function persist(array $attributes): self
    {
        $this->fill($attributes);

        if ($this->exists) {
            rescue(fn () => $this->save(), report: true);
        }

        return $this;
    }

    protected function sendNotification(): void
    {
        if (! $this->notify || ! $this->user_id) {
            return;
        }

        rescue(function () {
            $user = $this->user;
            if (! $user) {
                return;
            }

            $failed = $this->status === self::STATUS_FAILED;
            $notification = Notification::make()
                ->title($failed ? __(':taak is mislukt', ['taak' => $this->label]) : __(':taak is klaar', ['taak' => $this->label]))
                ->body($this->message);

            $failed ? $notification->danger() : $notification->success();

            if ($this->result_url) {
                $notification->actions([
                    \Filament\Actions\Action::make('open')
                        ->label($this->result_label ?: __('Bekijken'))
                        ->url($this->result_url),
                ]);
            }

            $notification->sendToDatabase($user);
        }, report: true);
    }
}
