<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Retention;

use Carbon\Carbon;
use Dashed\DashedCore\Models\PersonalDataRequest;
use Dashed\DashedCore\Retention\Contracts\Opruimer;

/**
 * Ruimt het ZIP-bestand van een afgehandeld inzageverzoek op en laat de rij
 * staan: die is het bewijs dat het verzoek is afgehandeld.
 */
class PersonalDataExportsOpruimer implements Opruimer
{
    public function ruimOp(Termijn $termijn, int $portie, bool $droog): int
    {
        $grens = Carbon::now()->subDays($termijn->dagen());
        $query = PersonalDataRequest::query()
            ->whereNotNull('file_path')
            ->where($termijn->datumkolom(), '<', $grens);

        if ($droog) {
            return $query->count();
        }

        $aantal = 0;
        $query->chunkById($portie, function ($verzoeken) use (&$aantal) {
            foreach ($verzoeken as $verzoek) {
                $verzoek->deleteFile();
                $aantal++;
            }
        });

        return $aantal;
    }
}
