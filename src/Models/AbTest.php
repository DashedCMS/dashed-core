<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Een A/B-test op één blok uit de ContentBuilder.
 *
 * Variant A is het blok zoals het op de pagina staat; variant B is hier
 * opgeslagen als één builder-item van hetzelfde bloktype. Het blok op de
 * pagina draagt het id van de test in `data._ab_test`, zodat de koppeling
 * blijft kloppen als blokken van volgorde wisselen.
 */
class AbTest extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_RUNNING = 'running';
    public const STATUS_FINISHED = 'finished';

    public const GOAL_CLICK = 'click';
    public const GOAL_ORDER = 'order';
    public const GOAL_FORM = 'form';

    /** De sleutel in de blokdata die naar de test wijst. */
    public const BLOCK_KEY = '_ab_test';

    protected $table = 'dashed__ab_tests';

    protected $guarded = [];

    protected $casts = [
        'variant_b' => 'array',
        'split_b' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function stats(): HasMany
    {
        return $this->hasMany(AbTestStat::class);
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(AbTestConversion::class);
    }

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    /** De data van variant B, of null als die nog niet is ingevuld. */
    public function variantBData(): ?array
    {
        $items = $this->variant_b;
        if (! is_array($items) || $items === []) {
            return null;
        }

        $item = reset($items);
        if (! is_array($item) || ! is_array($item['data'] ?? null)) {
            return null;
        }

        $data = $item['data'];
        unset($data[self::BLOCK_KEY]);

        return $data;
    }

    /** @return array<string, string> */
    public static function goalOptions(): array
    {
        return [
            self::GOAL_CLICK => __('Klik op een link of knop in het blok'),
            self::GOAL_ORDER => __('Betaalde bestelling'),
            self::GOAL_FORM => __('Verstuurd formulier'),
        ];
    }

    /** @return array<string, string> */
    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => __('Concept'),
            self::STATUS_RUNNING => __('Loopt'),
            self::STATUS_FINISHED => __('Afgerond'),
        ];
    }
}
