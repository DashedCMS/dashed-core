<?php

namespace Dashed\DashedCore\Livewire\Hooks;

use Throwable;
use Livewire\ComponentHook;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Pages\EditRecord;

/**
 * Twee mensen op hetzelfde record: zonder dit wint stil wie het laatst
 * opslaat. Deze hook hangt aan elke Filament-bewerkpagina, ook die van
 * klantprojecten, en onthoudt in de (ondertekende) memo van de snapshot
 * wanneer het record geladen of door deze pagina zelf opgeslagen is, plus een
 * hash per kolom. Is het record bij "Opslaan" in de database nieuwer, dan
 * gaat de opslag niet door en krijgt de browser het event
 * `dashed-save-conflict` met wie, wanneer en welke kolommen; de modal daarvoor
 * staat in `dashed-core::filament.save-conflict-modal`. "Toch overschrijven"
 * roept `dashedForceSave` aan, dat alleen deze hook kent.
 *
 * Een opslag via een andere route dan de methode `save` (de taalwissel in
 * HasEditableCMSActions bijvoorbeeld) wordt niet tegengehouden, maar telt wel
 * als eigen opslag: in dehydrate schuift het moment mee zodra het record in
 * dit verzoek nieuwer is geworden dan bij het laden.
 */
class SaveConflictGuard extends ComponentHook
{
    public const MEMO_LOADED = 'dashedLoadedAt';

    public const MEMO_HASHES = 'dashedLoadedHashes';

    public const FORCE_METHOD = 'dashedForceSave';

    protected ?int $loadedAt = null;

    /** @var array<string, string> */
    protected array $loadedHashes = [];

    protected ?int $hydratedAt = null;

    public function hydrate($memo): void
    {
        if (! $this->applies()) {
            return;
        }

        $this->loadedAt = isset($memo[self::MEMO_LOADED]) ? (int) $memo[self::MEMO_LOADED] : null;
        $this->loadedHashes = (array) ($memo[self::MEMO_HASHES] ?? []);
        $this->hydratedAt = $this->timestamp($this->record());
    }

    public function call($method, $params, $returnEarly)
    {
        if (! $this->applies()) {
            return;
        }

        if ($method === self::FORCE_METHOD) {
            // Bewust overschrijven: de huidige stand in de database geldt als
            // gelezen, en dan gewoon opslaan.
            $this->loadedAt = $this->hydratedAt;
            $this->component->save();
            $returnEarly();

            return;
        }

        if ($method !== 'save' || $this->loadedAt === null || $this->hydratedAt === null) {
            return;
        }

        if ($this->hydratedAt <= $this->loadedAt) {
            return;
        }

        $record = $this->record();

        $this->component->dispatch(
            'dashed-save-conflict',
            id: $this->component->getId(),
            by: $this->lastEditor($record),
            at: $record?->updated_at?->format('H:i'),
            fields: $this->changedFields($record),
        );

        $returnEarly();
    }

    public function dehydrate($context): void
    {
        if (! $this->applies()) {
            return;
        }

        $record = $this->record();
        $now = $this->timestamp($record);

        if ($now === null) {
            return;
        }

        // Eerste keer (na mount), of in dit verzoek door deze pagina zelf
        // opgeslagen: dit is de stand die de gebruiker nu voor zich heeft.
        if ($this->loadedAt === null || ($this->hydratedAt !== null && $now > $this->hydratedAt)) {
            $this->loadedAt = $now;
            $this->loadedHashes = $this->hashes($record);
        }

        $context->addMemo(self::MEMO_LOADED, $this->loadedAt);
        $context->addMemo(self::MEMO_HASHES, $this->loadedHashes);
    }

    protected function applies(): bool
    {
        return $this->component instanceof EditRecord;
    }

    protected function record(): ?Model
    {
        try {
            $record = $this->component->getRecord();
        } catch (Throwable) {
            return null;
        }

        return $record instanceof Model && $record->exists && $record->usesTimestamps() ? $record : null;
    }

    protected function timestamp(?Model $record): ?int
    {
        if (! $record) {
            return null;
        }

        // De record in de component kan in dit verzoek al gewijzigd zijn; de
        // vergelijking gaat altijd over wat er in de database staat.
        $value = $record->getAttribute($record->getUpdatedAtColumn());

        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }

        return $value ? (strtotime((string) $value) ?: null) : null;
    }

    /**
     * @return array<string, string>
     */
    protected function hashes(?Model $record): array
    {
        if (! $record) {
            return [];
        }

        $hashes = [];
        foreach ($record->getAttributes() as $key => $value) {
            if (in_array($key, [$record->getUpdatedAtColumn(), $record->getCreatedAtColumn()], true)) {
                continue;
            }
            $hashes[$key] = substr(md5(is_scalar($value) || $value === null ? (string) $value : json_encode($value)), 0, 12);
        }

        return $hashes;
    }

    /**
     * @return array<int, string>
     */
    protected function changedFields(?Model $record): array
    {
        if (! $record || ! $this->loadedHashes) {
            return [];
        }

        $fresh = $record->newQuery()->whereKey($record->getKey())->first();

        return collect($this->hashes($fresh))
            ->filter(fn (string $hash, string $key) => ($this->loadedHashes[$key] ?? null) !== $hash)
            ->keys()
            ->map(fn (string $key) => __(str_replace('_', ' ', ucfirst($key))))
            ->values()
            ->all();
    }

    protected function lastEditor(?Model $record): ?string
    {
        if (! $record || ! class_exists(\Spatie\Activitylog\Models\Activity::class)) {
            return null;
        }

        return rescue(function () use ($record) {
            $activity = \Spatie\Activitylog\Models\Activity::query()
                ->where('subject_type', $record->getMorphClass())
                ->where('subject_id', $record->getKey())
                ->whereNotNull('causer_id')
                ->latest('id')
                ->first();

            $causer = $activity?->causer;

            if (! $causer || $causer->getKey() === auth()->id()) {
                return null;
            }

            return $causer->name ?? trim(($causer->first_name ?? '').' '.($causer->last_name ?? '')) ?: $causer->email;
        }, null, false);
    }
}
