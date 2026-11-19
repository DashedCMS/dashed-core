<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Welke bestelling of formulierinzending bij welke variant hoorde. Een
 * bestelling staat er vanaf het afrekenen in en telt pas mee als hij
 * betaald is (`converted_at`); zo telt een order die nooit betaald wordt
 * niet als conversie, terwijl de toewijzing uit de browser van dat moment
 * bewaard blijft voor een betaling die later via een webhook binnenkomt.
 */
class AbTestConversion extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'dashed__ab_test_conversions';

    protected $guarded = [];

    protected $casts = [
        'converted_at' => 'datetime',
    ];
}
