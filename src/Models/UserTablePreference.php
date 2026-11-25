<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Eén onthouden tabelstand (filters, sortering, kolommen, per pagina of
 * groepering) van één gebruiker. De sleutel is de sessiesleutel van Filament
 * zonder het voorvoegsel `tables.`, zie {@see \Dashed\DashedCore\Classes\TablePreferences}.
 */
class UserTablePreference extends Model
{
    protected $table = 'dashed__user_table_preferences';

    protected $fillable = ['user_id', 'key', 'value'];

    protected $casts = [
        'value' => 'array',
    ];
}
