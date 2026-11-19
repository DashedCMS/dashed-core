<?php

namespace Dashed\DashedCore\Classes\Revisions;

use Closure;
use Filament\Actions\Action;
use Illuminate\Support\Facades\DB;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Schema;
use Dashed\DashedCore\Models\Revision;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Auth\Access\AuthorizationException;

/**
 * Goedkeuringsstroom: concept, ter beoordeling, goedgekeurd.
 *
 * Het ontwerp: het live record bevat altijd en alleen de goedgekeurde versie.
 * Een redacteur zonder het recht `approve_content` slaat niet op in het
 * record maar in een open concept (een Revision met status draft of pending).
 * Dat concept ontstaat door de gewone opslag van Filament in een transactie
 * te draaien, de verse databasestand vast te leggen en de transactie terug te
 * draaien. Goedkeuren schrijft het concept met gewone Eloquent-saves in het
 * record, en pas dan gaan de bestaande `saved`-hooks (responscache, menu's,
 * zoekindex) af. De front-end en de responscache zien zo nooit iets anders
 * dan de goedgekeurde versie, zonder één extra voorwaarde op een
 * front-endquery.
 *
 * Een model doet mee door zich aan te melden met
 * `cms()->builder('contentApprovalModels', [Model::class => fn () => __('Label')])`;
 * per site staat aan welke types de stroom hebben (standaard geen).
 */
class ContentApproval
{
    public const PERMISSION = 'approve_content';

    public const SETTING = 'content_approval_types';

    /** @var array<string, array<int, string>> */
    protected static array $publicColumn = [];

    /**
     * @return array<class-string, string>
     */
    public static function registeredTypes(): array
    {
        $types = [];

        foreach ((array) cms()->builder('contentApprovalModels') as $class => $label) {
            if (is_string($class) && class_exists($class)) {
                $types[$class] = $label instanceof Closure ? (string) $label() : __((string) $label);
            }
        }

        return $types;
    }

    public static function isRegistered(string $class): bool
    {
        return array_key_exists($class, (array) cms()->builder('contentApprovalModels'));
    }

    /**
     * @return array<int, string>
     */
    public static function enabledTypes(?string $siteId = null): array
    {
        $value = Customsetting::get(self::SETTING, $siteId ?? Sites::getActive(), '[]');

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        return array_values(array_filter((array) $value, 'is_string'));
    }

    public static function enabledFor(Model|string $model, ?string $siteId = null): bool
    {
        $class = is_string($model) ? $model : $model::class;

        if (! static::isRegistered($class)) {
            return false;
        }

        $sites = [$siteId ?? Sites::getActive()];

        if (! $siteId && $model instanceof Model) {
            $siteIds = $model->getAttribute('site_ids');
            $siteIds = is_string($siteIds) ? json_decode($siteIds, true) : $siteIds;

            if (is_array($siteIds) && $siteIds !== []) {
                $sites = $siteIds;
            }
        }

        foreach ($sites as $site) {
            if (in_array($class, static::enabledTypes((string) $site), true)) {
                return true;
            }
        }

        return false;
    }

    public static function mayPublish(?User $user): bool
    {
        return $user !== null && $user->can(self::PERMISSION);
    }

    public static function savesAsDraft(Model $model, ?User $user = null): bool
    {
        $user ??= auth()->user();

        return static::enabledFor($model) && ! static::mayPublish($user instanceof User ? $user : null);
    }

    public static function openDraft(Model $model): ?Revision
    {
        if (! Revisions::supports($model)) {
            return null;
        }

        return Revision::query()->forRecord($model)->open()->latest('id')->first();
    }

    /**
     * Schrijft een opname weg als het open concept van dit record (bijwerken
     * of nieuw). Een concept dat gelijk is aan live en nog niet bestond wordt
     * niet aangemaakt.
     */
    public static function storeDraft(Model $model, array $snapshot, ?User $user, string $reason = 'draft', ?int $restoredFromId = null): ?Revision
    {
        $hash = RevisionSnapshot::hash($snapshot);
        $draft = static::openDraft($model);

        if ($draft) {
            if ($draft->hash !== $hash || $restoredFromId) {
                $draft->update([
                    'snapshot' => $snapshot,
                    'hash' => $hash,
                    'user_id' => $user?->getKey(),
                    'reason' => $reason,
                    'restored_from_id' => $restoredFromId ?? $draft->restored_from_id,
                ]);
            }

            return $draft;
        }

        Revisions::ensureBaseline($model);
        $live = Revisions::latestLive($model);

        if ($live?->hash === $hash) {
            return null;
        }

        return Revision::create([
            'revisionable_type' => $model::class,
            'revisionable_id' => (string) $model->getKey(),
            'user_id' => $user?->getKey(),
            'status' => Revision::STATUS_DRAFT,
            'reason' => $reason,
            'restored_from_id' => $restoredFromId,
            'base_revision_id' => $live?->id,
            'snapshot' => $snapshot,
            'hash' => $hash,
        ]);
    }

    public static function submit(Revision $draft, User $user): void
    {
        if (! $draft->isOpen()) {
            return;
        }

        $draft->update([
            'status' => Revision::STATUS_PENDING,
            'submitted_by' => $user->getKey(),
            'submitted_at' => now(),
        ]);

        static::notifyReviewers($draft, $user);
    }

    /**
     * Zet het goedgekeurde concept live. De goedgekeurde rij is daarna zelf
     * de live revisie, met de opname zoals hij na het schrijven echt staat.
     */
    public static function approve(Revision $draft, User $reviewer, ?string $comment = null): void
    {
        if (! static::mayPublish($reviewer)) {
            throw new AuthorizationException(__('Je mag geen inhoud goedkeuren.'));
        }

        $model = $draft->revisionable();

        if (! $model || ! $draft->isOpen()) {
            return;
        }

        Revisions::ensureBaseline($model);

        DB::connection($model->getConnectionName())->transaction(
            fn () => RevisionSnapshot::apply($model, $draft->snapshot),
        );

        $snapshot = RevisionSnapshot::take($model);

        $draft->update([
            'status' => Revision::STATUS_APPROVED,
            'snapshot' => $snapshot,
            'hash' => RevisionSnapshot::hash($snapshot),
            'reviewed_by' => $reviewer->getKey(),
            'reviewed_at' => now(),
            'review_comment' => filled($comment) ? $comment : $draft->review_comment,
        ]);

        static::notifyAuthor($draft, $reviewer, __('Goedgekeurd: :titel', ['titel' => static::titleOf($model)]), $comment, true);
    }

    /**
     * Afkeuren zet het concept terug op concept, met de opmerking erbij. De
     * redacteur werkt in hetzelfde concept verder en biedt het opnieuw aan.
     */
    public static function reject(Revision $draft, User $reviewer, string $comment): void
    {
        if (! static::mayPublish($reviewer)) {
            throw new AuthorizationException(__('Je mag geen inhoud afkeuren.'));
        }

        if (! $draft->isOpen()) {
            return;
        }

        $draft->update([
            'status' => Revision::STATUS_DRAFT,
            'reviewed_by' => $reviewer->getKey(),
            'reviewed_at' => now(),
            'rejected_at' => now(),
            'review_comment' => $comment,
        ]);

        $model = $draft->revisionable();

        static::notifyAuthor($draft, $reviewer, __('Afgekeurd: :titel', ['titel' => $model ? static::titleOf($model) : '#' . $draft->revisionable_id]), $comment, false);
    }

    public static function discard(Revision $draft): void
    {
        if ($draft->isOpen()) {
            $draft->update(['status' => Revision::STATUS_DISCARDED]);
        }
    }

    /**
     * Een directe publicatie (door iemand met publiceerrecht, of een
     * teruggezette versie) haalt het open concept in.
     */
    public static function supersedeOpenDrafts(Model $model): void
    {
        Revision::query()->forRecord($model)->open()->update(['status' => Revision::STATUS_SUPERSEDED]);
    }

    /**
     * Een nieuw item van een redacteur zonder publiceerrecht staat nooit
     * meteen online: `public` gaat op 0 en het item komt pas online via een
     * goedgekeurd concept.
     */
    public static function guardCreate(Model $model): void
    {
        if (! static::isRegistered($model::class) || ! auth()->check()) {
            return;
        }

        $user = auth()->user();

        if (! $user instanceof User || static::mayPublish($user) || ! static::enabledFor($model)) {
            return;
        }

        $table = $model->getTable();
        static::$publicColumn[$table] ??= rescue(fn () => Schema::getColumnListing($table), [], false);

        if (in_array('public', static::$publicColumn[$table], true)) {
            $model->setAttribute('public', 0);
        }
    }

    public static function titleOf(Model $model): string
    {
        foreach (['name', 'title'] as $field) {
            $value = method_exists($model, 'isTranslatableAttribute') && $model->isTranslatableAttribute($field)
                ? $model->getTranslation($field, app()->getLocale())
                : $model->getAttribute($field);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '#' . $model->getKey();
    }

    public static function editUrl(Model $model): ?string
    {
        return rescue(function () use ($model) {
            $resource = Filament::getModelResource($model);

            return $resource ? $resource::getUrl('edit', ['record' => $model]) : null;
        }, null, false);
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    public static function reviewers()
    {
        return static::userModel()::query()
            ->where(function ($query) {
                $query->where('role', 'superadmin')
                    ->orWhereHas('roles', fn ($roles) => $roles->whereJsonContains('extra_permissions', self::PERMISSION));
            })
            ->get();
    }

    /**
     * De gebruikersklasse van het project (App\\Models\\User erft van die van
     * dashed-core). Het belletje leest meldingen op de morph-klasse van de
     * ingelogde gebruiker, dus een melding aan de basisklasse komt nooit aan.
     *
     * @return class-string<User>
     */
    protected static function userModel(): string
    {
        $class = config('auth.providers.users.model');

        return is_string($class) && is_a($class, User::class, true) ? $class : User::class;
    }

    protected static function notifyReviewers(Revision $draft, User $submitter): void
    {
        $model = $draft->revisionable();

        if (! $model) {
            return;
        }

        $url = static::editUrl($model);
        $title = __('Ter beoordeling: :titel', ['titel' => static::titleOf($model)]);
        $body = __(':naam vraagt je dit concept te beoordelen.', ['naam' => Revision::userLabel($submitter)]);

        foreach (static::reviewers() as $reviewer) {
            if ($reviewer->is($submitter)) {
                continue;
            }

            rescue(fn () => Notification::make()
                ->title($title)
                ->body($body)
                ->warning()
                ->actions($url ? [Action::make('open')->label(__('Beoordelen'))->url($url)] : [])
                ->sendToDatabase($reviewer), report: false);
        }
    }

    protected static function notifyAuthor(Revision $draft, User $reviewer, string $title, ?string $comment, bool $approved): void
    {
        $authorId = $draft->submitted_by ?? $draft->user_id;

        if (! $authorId || (string) $authorId === (string) $reviewer->getKey()) {
            return;
        }

        $author = static::userModel()::query()->find($authorId);

        if (! $author) {
            return;
        }

        $model = $draft->revisionable();
        $url = $model ? static::editUrl($model) : null;

        rescue(fn () => Notification::make()
            ->title($title)
            ->body(filled($comment) ? $comment : null)
            ->{$approved ? 'success' : 'danger'}()
            ->actions($url ? [Action::make('open')->label(__('Openen'))->url($url)] : [])
            ->sendToDatabase($author), report: false);
    }
}
