<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Retention;

use Carbon\Carbon;
use Dashed\DashedCore\Models\NotFoundPage;
use Dashed\DashedCore\Retention\Contracts\Opruimer;
use Dashed\DashedCore\Models\NotFoundPageOccurrence;

/**
 * Verwijdert 404-registraties en telt daarna de teller op de ouderrij opnieuw.
 * Die teller is het enige wat er van een oude 404 overblijft, dus als hij niet
 * meeloopt met het opruimen liegt het scherm.
 */
class NotFoundOccurrenceOpruimer implements Opruimer
{
    public function ruimOp(Termijn $termijn, int $portie, bool $droog): int
    {
        $grens = Carbon::now()->subDays($termijn->dagen());

        if ($droog) {
            return NotFoundPageOccurrence::where($termijn->datumkolom(), '<', $grens)->count();
        }

        $aantal = 0;

        NotFoundPageOccurrence::where($termijn->datumkolom(), '<', $grens)
            ->chunkById($portie, function ($occurrences) use (&$aantal) {
                $geraakt = [];

                foreach ($occurrences as $occurrence) {
                    $geraakt[$occurrence->not_found_page_id] = true;
                    $occurrence->forceDelete();
                    $aantal++;
                }

                NotFoundPage::whereIn('id', array_keys($geraakt))
                    ->withTrashed()
                    ->each(function (NotFoundPage $page) {
                        $page->forceFill([
                            'total_occurrences' => $page->occurrences()->count(),
                            'last_occurrence' => $page->occurrences()->latest('created_at')->value('created_at'),
                        ])->save();
                    });
            });

        return $aantal;
    }
}
