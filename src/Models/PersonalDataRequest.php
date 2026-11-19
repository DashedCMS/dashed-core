<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Dashed\DashedCore\PersonalData\PersonalDataSubject;

/**
 * Een AVG-verzoek: inzage (export) of verwijdering, per e-mailadres. De rij
 * blijft altijd staan als bewijs dat het verzoek is afgehandeld; na een
 * geslaagde verwijdering staat alleen nog de hash van het adres erin.
 *
 * Bewust zonder LogsActivity: het activiteitenlogboek zou het adres anders
 * alsnog bewaren.
 */
class PersonalDataRequest extends Model
{
    protected $table = 'dashed__personal_data_requests';

    public const TYPE_EXPORT = 'export';

    public const TYPE_ERASE = 'erase';

    public const STATUS_NEW = 'new';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    protected $guarded = [];

    protected $casts = [
        'preview' => 'array',
        'log' => 'array',
        'requested_at' => 'date',
        'previewed_at' => 'datetime',
        'queued_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'file_deleted_at' => 'datetime',
        'file_size' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (PersonalDataRequest $request) {
            if ($request->isDirty('email') && filled($request->email)) {
                $request->email = PersonalDataSubject::normalize($request->email);
                $request->email_hash = PersonalDataSubject::hash($request->email);
            }
        });
    }

    /** @return array<string, string> */
    public static function typeLabels(): array
    {
        return [
            self::TYPE_EXPORT => __('Inzage (export)'),
            self::TYPE_ERASE => __('Verwijdering'),
        ];
    }

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_NEW => __('Nieuw'),
            self::STATUS_QUEUED => __('In wachtrij'),
            self::STATUS_PROCESSING => __('Bezig'),
            self::STATUS_COMPLETED => __('Afgehandeld'),
            self::STATUS_FAILED => __('Mislukt'),
        ];
    }

    /** @return array<string, string> */
    public static function statusColors(): array
    {
        return [
            self::STATUS_NEW => 'gray',
            self::STATUS_QUEUED => 'info',
            self::STATUS_PROCESSING => 'warning',
            self::STATUS_COMPLETED => 'success',
            self::STATUS_FAILED => 'danger',
        ];
    }

    /** @return array<string, string> */
    public static function verificationLabels(): array
    {
        return [
            'same_email' => __('Verzoek kwam van hetzelfde e-mailadres'),
            'logged_in' => __('Ingelogd in het eigen account'),
            'email_confirmed' => __('Bevestigd via een mail naar het adres'),
            'id_checked' => __('Identiteit op een andere manier gecontroleerd'),
            'other' => __('Anders (zie toelichting)'),
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model') ?: User::class, 'created_by');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model') ?: User::class, 'executed_by');
    }

    public function isErase(): bool
    {
        return $this->type === self::TYPE_ERASE;
    }

    public function isRunning(): bool
    {
        return in_array($this->status, [self::STATUS_QUEUED, self::STATUS_PROCESSING], true);
    }

    /**
     * Uitvoeren kan na een droog overzicht, zolang het verzoek niet loopt
     * of al geslaagd is. Een mislukte poging mag opnieuw.
     */
    public function canBeExecuted(): bool
    {
        return filled($this->email)
            && $this->previewed_at !== null
            && in_array($this->status, [self::STATUS_NEW, self::STATUS_FAILED], true);
    }

    public function hasDownload(): bool
    {
        return $this->type === self::TYPE_EXPORT
            && filled($this->file_path)
            && rescue(fn () => Storage::disk($this->file_disk)->exists($this->file_path), false, report: false);
    }

    public function deleteFile(): void
    {
        if ($this->file_path) {
            rescue(fn () => Storage::disk($this->file_disk)->delete($this->file_path), report: false);
        }

        $this->forceFill(['file_path' => null, 'file_size' => null, 'file_deleted_at' => now()])->save();
    }

    public function subject(): PersonalDataSubject
    {
        return PersonalDataSubject::forEmail((string) $this->email);
    }
}
