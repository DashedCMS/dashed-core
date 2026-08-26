<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;

/**
 * Verouderd: exports staan aangemeld in het bewaartermijnenregister en worden
 * nu opgeruimd door dashed:prune. Dit command blijft bestaan als alias,
 * zodat een bestaande cronregel op een productieserver niet in één klap
 * kapot gaat.
 */
class CleanupOldExports extends Command
{
    protected $signature = 'dashed:cleanup-old-exports';

    protected $description = 'Verouderd. Gebruik dashed:prune. Delete exports older than the configured retention period.';

    public function handle(): int
    {
        return $this->call('dashed:prune', [
            '--only' => 'exports',
        ]);
    }
}
