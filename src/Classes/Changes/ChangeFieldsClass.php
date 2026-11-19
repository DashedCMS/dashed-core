<?php

namespace Dashed\DashedCore\Classes\Changes;

use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Van een opgeslagen morph-type terug naar de modelklasse.
 */
class ChangeFieldsClass
{
    public static function resolve(string $modelType): string
    {
        return Relation::getMorphedModel($modelType) ?? $modelType;
    }
}
