<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Retention;

use Carbon\Carbon;
use Dashed\DashedCore\Models\Export;
use Dashed\DashedCore\Retention\Contracts\Opruimer;

/**
 * Loopt via het Eloquent-model, niet via de generieke TabelOpruimer. Een kale
 * databaseverwijdering slaat Export::deleting over, en dat is de listener die
 * het bestand van de opslag haalt. Zonder deze omweg verdwijnt de rij en
 * blijft het bestand permanent staan, want de scheduler draait dit dagelijks
 * opnieuw en raakt dezelfde oude rijen dan al niet meer.
 */
class ExportsOpruimer implements Opruimer
{
    public function ruimOp(Termijn $termijn, int $portie, bool $droog): int
    {
        $grens = Carbon::now()->subDays($termijn->dagen());

        if ($droog) {
            return Export::where($termijn->datumkolom(), '<', $grens)->count();
        }

        $aantal = 0;

        Export::where($termijn->datumkolom(), '<', $grens)
            ->chunkById($portie, function ($exports) use (&$aantal) {
                foreach ($exports as $export) {
                    $export->delete();
                    $aantal++;
                }
            });

        return $aantal;
    }
}
