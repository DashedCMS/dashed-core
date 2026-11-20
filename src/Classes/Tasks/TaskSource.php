<?php

namespace Dashed\DashedCore\Classes\Tasks;

use Closure;
use Illuminate\Database\Eloquent\Model;

/**
 * Een bron van open werk op "Mijn taken": een soort record waar iemand actie
 * op moet nemen (nieuwe aanvraag, ongelezen formulier, retour, ...). De pagina
 * toont per bron een tegel met het aantal; bronnen met `items` leveren ook de
 * losse records voor de takentabel.
 */
class TaskSource
{
    /** Maximaal aantal records per bron in de takentabel. */
    public const ITEM_LIMIT = 250;

    public function __construct(
        public string $key,
        public Closure $label,
        public Closure $count,
        public Closure $url,
        public ?Closure $icon = null,
        public ?Closure $visible = null,
        public ?string $group = null,
        public int $sort = 0,
        public ?Closure $items = null,
    ) {
    }

    /**
     * Bron op basis van een Filament-resource: de lijst van de resource is het doel
     * van de tegel en canViewAny() bepaalt wie hem ziet.
     *
     * Met `query` (een closure die de Eloquent-query van de open records geeft) telt
     * de bron die query en komen de records in de takentabel. `title`, `description`
     * en `recordUrl` krijgen het record; standaard de recordtitel van de resource en
     * de view- of edit-pagina, anders de lijst. Zonder `query` telt het menu-badge-getal.
     */
    public static function resource(
        string $resource,
        ?string $label = null,
        ?string $group = null,
        int $sort = 0,
        ?Closure $count = null,
        string $page = 'index',
        ?Closure $query = null,
        ?Closure $title = null,
        ?Closure $description = null,
        ?Closure $recordUrl = null,
        string $dateColumn = 'created_at',
    ): self {
        $count ??= $query
            ? fn () => $query()->count()
            : fn () => (int) $resource::getNavigationBadge();

        $items = $query ? function () use ($resource, $query, $title, $description, $recordUrl, $dateColumn, $page) {
            $pages = $resource::getPages();
            $defaultUrl = function (Model $record) use ($resource, $pages, $page) {
                foreach (['view', 'edit'] as $recordPage) {
                    if (isset($pages[$recordPage])) {
                        return $resource::getUrl($recordPage, ['record' => $record]);
                    }
                }

                return $resource::getUrl($page);
            };

            return $query()
                ->limit(self::ITEM_LIMIT)
                ->get()
                ->map(fn (Model $record) => [
                    'id' => $record->getKey(),
                    'title' => (string) ($title ? $title($record) : ($resource::getRecordTitle($record) ?: '#' . $record->getKey())),
                    'description' => $description ? $description($record) : null,
                    'date' => $record->getAttribute($dateColumn),
                    'url' => rescue(fn () => $recordUrl ? $recordUrl($record) : $defaultUrl($record), null, report: false),
                ]);
        } : null;

        return new self(
            key: $resource,
            label: fn () => $label ?? $resource::getNavigationLabel(),
            count: $count,
            url: fn () => $resource::getUrl($page),
            icon: fn () => $resource::getNavigationIcon(),
            visible: fn () => $resource::canViewAny(),
            group: $group,
            sort: $sort,
            items: $items,
        );
    }

    /**
     * Vrije bron. `items` geeft een lijst van ['id', 'title', 'description', 'date', 'url'].
     */
    public static function make(string $key, string|Closure $label, Closure $count, string|Closure $url, ?string $icon = null, ?string $group = null, int $sort = 0, ?Closure $visible = null, ?Closure $items = null): self
    {
        return new self(
            key: $key,
            label: $label instanceof Closure ? $label : fn () => $label,
            count: $count,
            url: $url instanceof Closure ? $url : fn () => $url,
            icon: $icon ? fn () => $icon : null,
            visible: $visible,
            group: $group,
            sort: $sort,
            items: $items,
        );
    }
}
