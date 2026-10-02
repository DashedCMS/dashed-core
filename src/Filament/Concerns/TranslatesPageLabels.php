<?php

namespace Dashed\DashedCore\Filament\Concerns;

use Illuminate\Contracts\Support\Htmlable;

/**
 * Statische eigenschappen kunnen geen __() bevatten, dus de vertaling gebeurt
 * bij het ophalen. De ouderklasse beslist welk label het wordt (een
 * bewerkpagina gebruikt voor zijn navigatielabel bijvoorbeeld niet $title);
 * komt dat label letterlijk uit $navigationLabel of $title, dan gaat het door
 * __(). Zo blijft de terugvallogica van Filament precies staan.
 */
trait TranslatesPageLabels
{
    public function getTitle(): string | Htmlable
    {
        return self::vertaalStatischPaginalabel(parent::getTitle());
    }

    public static function getNavigationLabel(): string
    {
        if (method_exists(parent::class, 'getNavigationLabel')) {
            return self::vertaalStatischPaginalabel(parent::getNavigationLabel());
        }

        // Een resourcepagina zonder eigen navigatie (CreateRecord, een losse
        // resourcepagina) kent de methode niet; val dan terug op de titel.
        return self::vertaalStatischPaginalabel(static::$title ?? (string) str(class_basename(static::class))
            ->kebab()
            ->replace('-', ' ')
            ->ucwords());
    }

    private static function vertaalStatischPaginalabel(string | Htmlable $label): string | Htmlable
    {
        if (! is_string($label) || $label === '') {
            return $label;
        }

        foreach (['navigationLabel', 'title'] as $eigenschap) {
            if (property_exists(static::class, $eigenschap) && static::${$eigenschap} === $label) {
                return __($label);
            }
        }

        return $label;
    }
}
