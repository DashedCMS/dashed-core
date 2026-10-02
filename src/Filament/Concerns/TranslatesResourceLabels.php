<?php

namespace Dashed\DashedCore\Filament\Concerns;

use Illuminate\Support\Str;

use function Filament\Support\get_model_label;
use function Filament\Support\locale_has_pluralization;

/**
 * Statische eigenschappen kunnen geen __() bevatten, dus de vertaling gebeurt
 * bij het ophalen. De Nederlandse tekst in de eigenschap is de sleutel in
 * nl.json. De terugvallogica is die van Filament's HasLabels en HasNavigation.
 */
trait TranslatesResourceLabels
{
    public static function getLabel(): ?string
    {
        return static::$label === null ? null : __(static::$label);
    }

    public static function getPluralLabel(): ?string
    {
        return static::$pluralLabel === null ? null : __(static::$pluralLabel);
    }

    public static function getModelLabel(): string
    {
        if (static::$modelLabel !== null) {
            return __(static::$modelLabel);
        }

        return static::getLabel() ?? get_model_label(static::getModel());
    }

    public static function getPluralModelLabel(): string
    {
        $label = static::$pluralModelLabel !== null ? __(static::$pluralModelLabel) : static::getPluralLabel();

        if (filled($label)) {
            return $label;
        }

        if (locale_has_pluralization()) {
            return Str::plural(static::getModelLabel());
        }

        return static::getModelLabel();
    }

    public static function getNavigationLabel(): string
    {
        return static::$navigationLabel !== null
            ? __(static::$navigationLabel)
            : static::getTitleCasePluralModelLabel();
    }
}
