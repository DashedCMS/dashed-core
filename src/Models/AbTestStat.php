<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Dagtellers per variant. Vertoningen, kliks en conversies tellen elk één
 * keer per browser per test; de browser onthoudt wat hij al gemeld heeft.
 */
class AbTestStat extends Model
{
    public $timestamps = false;

    protected $table = 'dashed__ab_test_stats';

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
    ];
}
