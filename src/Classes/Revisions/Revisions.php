<?php

namespace Dashed\DashedCore\Classes\Revisions;

use Illuminate\Support\Facades\DB;
use Dashed\DashedCore\Models\User;
use Illuminate\Support\Facades\Schema;
use Dashed\DashedCore\Models\Revision;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Revisies per record. Een revisie wordt vastgelegd na elke opslag via de
 * bewerkpagina (HasEditableCMSActions), bij terugzetten en bij goedkeuren.
 * Bewust niet op elk `saved`-event: één opslag van een pagina schrijft het
 * record, de metadata en de extra blokken los weg, en dat zouden drie halve
 * revisies worden.
 */
class Revisions
{
    protected static ?bool $tableExists = null;

    public static function supports(?Model $model): bool
    {
        if (! $model || ! $model->exists) {
            return false;
        }

        static::$tableExists ??= rescue(fn () => Schema::hasTable('dashed__revisions'), false, false);

        return static::$tableExists;
    }

    public static function flush(): void
    {
        static::$tableExists = null;
        RevisionSnapshot::flushColumnCache();
    }

    public static function latestLive(Model $model): ?Revision
    {
        return Revision::query()->forRecord($model)->live()->latest('id')->first();
    }

    /**
     * De stand van vóór de eerste bewerking via deze functie, zodat ook die
     * terug te zetten is. Draait vóór het opslaan.
     */
    public static function ensureBaseline(Model $model): void
    {
        if (! static::supports($model) || Revision::query()->forRecord($model)->live()->exists()) {
            return;
        }

        $snapshot = RevisionSnapshot::take($model);

        Revision::create([
            'revisionable_type' => $model::class,
            'revisionable_id' => (string) $model->getKey(),
            'user_id' => null,
            'status' => Revision::STATUS_PUBLISHED,
            'reason' => 'baseline',
            'snapshot' => $snapshot,
            'hash' => RevisionSnapshot::hash($snapshot),
        ]);
    }

    /**
     * Legt de huidige databasestand vast als live revisie. Niets veranderd
     * ten opzichte van de vorige live revisie: geen nieuwe rij.
     */
    public static function record(Model $model, string $reason = 'save', ?User $user = null, ?int $restoredFromId = null): ?Revision
    {
        if (! static::supports($model)) {
            return null;
        }

        $snapshot = RevisionSnapshot::take($model);
        $hash = RevisionSnapshot::hash($snapshot);

        if (static::latestLive($model)?->hash === $hash) {
            return null;
        }

        return Revision::create([
            'revisionable_type' => $model::class,
            'revisionable_id' => (string) $model->getKey(),
            'user_id' => $user?->getKey(),
            'status' => Revision::STATUS_PUBLISHED,
            'reason' => $reason,
            'restored_from_id' => $restoredFromId,
            'snapshot' => $snapshot,
            'hash' => $hash,
        ]);
    }

    /**
     * Zet een oude versie terug en legt dat zelf weer als revisie vast. Staat
     * de goedkeuringsstroom aan en mag de gebruiker niet publiceren, dan wordt
     * de oude versie het open concept in plaats van live te gaan.
     */
    public static function restore(Revision $revision, User $user): ?Revision
    {
        $model = $revision->revisionable();

        if (! $model) {
            return null;
        }

        if (! $user->can('update', $model)) {
            throw new AuthorizationException(__('Je mag dit item niet bewerken.'));
        }

        if (ContentApproval::savesAsDraft($model, $user)) {
            return ContentApproval::storeDraft($model, $revision->snapshot, $user, 'restore', $revision->id);
        }

        static::ensureBaseline($model);

        DB::connection($model->getConnectionName())->transaction(
            fn () => RevisionSnapshot::apply($model, $revision->snapshot),
        );

        $new = static::record($model->fresh() ?? $model, 'restore', $user, $revision->id);

        if (ContentApproval::enabledFor($model)) {
            ContentApproval::supersedeOpenDrafts($model);
        }

        return $new;
    }
}
