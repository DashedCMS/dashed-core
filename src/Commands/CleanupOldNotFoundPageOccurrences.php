<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;

/**
 * Verouderd: 404-registraties staan aangemeld in het bewaartermijnenregister
 * en worden nu opgeruimd door dashed:prune. Dit command blijft bestaan als
 * alias, zodat een bestaande cronregel op een productieserver niet in één
 * klap kapot gaat.
 */
class CleanupOldNotFoundPageOccurrences extends Command
{
    protected $signature = 'dashed:cleanup-old-not-found-page-occurrences';

    protected $description = 'Verouderd. Gebruik dashed:prune. Verwijder 404-occurrences ouder dan de geconfigureerde bewaartermijn.';

    public function handle(): int
    {
        return $this->call('dashed:prune', [
            '--only' => 'not_found_page_occurrences',
        ]);
    }
}
