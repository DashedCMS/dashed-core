<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;

/**
 * Verouderd: verzonden e-mails staan aangemeld in het bewaartermijnenregister
 * en worden nu opgeruimd door dashed:prune. Dit command blijft bestaan als
 * alias, zodat een bestaande cronregel op een productieserver niet in één
 * klap kapot gaat.
 */
class PruneSentEmailsCommand extends Command
{
    protected $signature = 'dashed:prune-sent-emails';

    protected $description = 'Verouderd. Gebruik dashed:prune. Verwijder verzonden e-mails ouder dan de geconfigureerde bewaarperiode.';

    public function handle(): int
    {
        return $this->call('dashed:prune', [
            '--only' => 'sent_emails',
        ]);
    }
}
