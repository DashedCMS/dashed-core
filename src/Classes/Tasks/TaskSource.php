<?php

namespace Dashed\DashedCore\Classes\Tasks;

use Closure;

/**
 * Een bron van open werk op "Mijn taken": een soort record waar iemand actie
 * op moet nemen (nieuwe aanvraag, ongelezen formulier, retour, ...). De pagina
 * toont per bron een tegel met het aantal en een link naar de lijst.
 */
class TaskSource
{
    public function __construct(
        public string $key,
        public Closure $label,
        public Closure $count,
        public Closure $url,
        public ?Closure $icon = null,
        public ?Closure $visible = null,
        public ?string $group = null,
        public int $sort = 0,
    ) {
    }

    /**
     * Bron op basis van een Filament-resource: het menu-badge-getal is het aantal,
     * de lijst van de resource het doel, en canViewAny() bepaalt wie hem ziet.
     */
    public static function resource(string $resource, ?string $label = null, ?string $group = null, int $sort = 0, ?Closure $count = null, string $page = 'index'): self
    {
        return new self(
            key: $resource,
            label: fn () => $label ?? $resource::getNavigationLabel(),
            count: $count ?? fn () => (int) $resource::getNavigationBadge(),
            url: fn () => $resource::getUrl($page),
            icon: fn () => $resource::getNavigationIcon(),
            visible: fn () => $resource::canViewAny(),
            group: $group,
            sort: $sort,
        );
    }

    public static function make(string $key, string|Closure $label, Closure $count, string|Closure $url, ?string $icon = null, ?string $group = null, int $sort = 0, ?Closure $visible = null): self
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
        );
    }
}
