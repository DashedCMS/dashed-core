<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Retention;

use Carbon\Carbon;
use Dashed\DashedCore\Classes\Trash\Trash;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Retention\Contracts\Opruimer;

/**
 * Leegt de prullenbak voor de modellen met `purge`, via het model zodat de
 * eigen opruimhaken (metadata, blokken) meelopen. Alleen wat na de invoering
 * van de prullenbak is weggegooid: een installatie kan jaren aan oude
 * soft-deletes hebben waar niemand bij stilstond, en die horen niet bij de
 * eerste nachtelijke ronde onherroepelijk te verdwijnen. Die blijven zichtbaar
 * in de prullenbak en gaan alleen met de hand weg.
 */
class PrullenbakOpruimer implements Opruimer
{
    public const INTRODUCED_KEY = 'trash_introduced_at';

    public function ruimOp(Termijn $termijn, int $portie, bool $droog): int
    {
        $grens = Carbon::now()->subDays($termijn->dagen());
        $sinds = self::introducedAt();
        $aantal = 0;

        foreach (Trash::types() as $model => $meta) {
            if (! $meta['purge']) {
                continue;
            }

            $kolom = (new $model())->getDeletedAtColumn();
            $query = $model::onlyTrashed()
                ->where($kolom, '<', $grens)
                ->where($kolom, '>=', $sinds);

            if ($droog) {
                $aantal += $query->count();

                continue;
            }

            $query->chunkById($portie, function ($rows) use (&$aantal) {
                foreach ($rows as $row) {
                    $row->forceDelete();
                    $aantal++;
                }
            });
        }

        return $aantal;
    }

    public static function introducedAt(): Carbon
    {
        // Installatiebreed, dus op de eerste site (zie CmsIpAllowlist).
        $siteId = \Dashed\DashedCore\Classes\Sites::getFirstSite()['id'] ?? null;
        $value = Customsetting::get(self::INTRODUCED_KEY, $siteId);

        if (! $value) {
            $value = Carbon::now()->toIso8601String();
            Customsetting::set(self::INTRODUCED_KEY, $value, $siteId);
        }

        return Carbon::parse($value);
    }
}
