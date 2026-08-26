<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;

/**
 * Verouderd: het activiteitenlogboek staat aangemeld in het
 * bewaartermijnenregister en wordt nu opgeruimd door dashed:prune. Dit
 * command blijft bestaan als alias, zodat een bestaande cronregel op een
 * productieserver niet in één klap kapot gaat.
 */
class PruneActivityLogCommand extends Command
{
    protected $signature = 'dashed:prune-activity-log {--chunk=1000 : Hoeveel regels per portie}';

    protected $description = 'Verouderd. Gebruik dashed:prune. Verwijdert het activiteitenlogboek volgens de bewaartermijn.';

    public function handle(): int
    {
        return $this->call('dashed:prune', [
            '--only' => 'activity_log',
            '--chunk' => $this->option('chunk'),
        ]);
    }
}
