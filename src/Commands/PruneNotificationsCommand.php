<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;

/**
 * Verouderd: meldingen staan aangemeld in het bewaartermijnenregister (twee
 * termijnen: na lezen en hard) en worden nu opgeruimd door dashed:prune. Dit
 * command blijft bestaan als alias, zodat een bestaande cronregel op een
 * productieserver niet in één klap kapot gaat.
 */
class PruneNotificationsCommand extends Command
{
    protected $signature = 'dashed:prune-notifications {--chunk=1000 : Hoeveel meldingen per portie}';

    protected $description = 'Verouderd. Gebruik dashed:prune. Verwijdert meldingen die ouder zijn dan de bewaartermijn.';

    public function handle(): int
    {
        return $this->call('dashed:prune', [
            '--only' => 'notifications',
            '--chunk' => $this->option('chunk'),
        ]);
    }
}
