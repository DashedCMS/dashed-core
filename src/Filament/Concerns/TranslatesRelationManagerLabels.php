<?php

namespace Dashed\DashedCore\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Statische eigenschappen kunnen geen __() bevatten, dus de vertaling gebeurt
 * bij het ophalen. De Nederlandse tekst in de eigenschap is de sleutel in
 * nl.json. De terugvallogica is die van Filament's RelationManager: model-
 * en meervoudslabel vallen terug op $label en $pluralLabel, en gaan zo naar
 * de tabel (makeTable()).
 */
trait TranslatesRelationManagerLabels
{
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return static::$title !== null ? __(static::$title) : parent::getTitle($ownerRecord, $pageClass);
    }

    protected static function getRecordLabel(): ?string
    {
        return static::$label === null ? null : __(static::$label);
    }

    protected static function getModelLabel(): ?string
    {
        return static::$modelLabel !== null ? __(static::$modelLabel) : static::getRecordLabel();
    }

    protected static function getPluralRecordLabel(): ?string
    {
        return static::$pluralLabel === null ? null : __(static::$pluralLabel);
    }

    protected static function getPluralModelLabel(): ?string
    {
        return static::$pluralModelLabel !== null ? __(static::$pluralModelLabel) : static::getPluralRecordLabel();
    }
}
