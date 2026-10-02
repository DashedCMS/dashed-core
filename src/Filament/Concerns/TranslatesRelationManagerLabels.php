<?php

namespace Dashed\DashedCore\Filament\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Statische eigenschappen kunnen geen __() bevatten, dus de vertaling gebeurt
 * bij het ophalen. De Nederlandse tekst in $title is de sleutel in nl.json.
 */
trait TranslatesRelationManagerLabels
{
    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return static::$title !== null ? __(static::$title) : parent::getTitle($ownerRecord, $pageClass);
    }
}
