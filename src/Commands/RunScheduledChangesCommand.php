<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Classes\Changes\ScheduledChangeRunner;

class RunScheduledChangesCommand extends Command
{
    protected $signature = 'dashed:run-scheduled-changes';

    protected $description = 'Voert ingeplande wijzigingen uit die aan de beurt zijn en draait verlopen wijzigingen terug';

    public function handle(): int
    {
        $result = ScheduledChangeRunner::runDue();

        $this->info(sprintf('Uitgevoerd: %d, teruggedraaid: %d, mislukt: %d', $result['applied'], $result['reverted'], $result['failed']));

        return self::SUCCESS;
    }
}
