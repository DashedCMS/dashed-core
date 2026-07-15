<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Support\Carbon;
use Illuminate\Console\Command;
use Dashed\DashedCore\Models\SentEmail;

class PruneSentEmailsCommand extends Command
{
    protected $signature = 'dashed:prune-sent-emails';

    protected $description = 'Verwijder verzonden e-mails ouder dan de geconfigureerde bewaarperiode.';

    public function handle(): int
    {
        $days = (int) config('dashed-core.sent_emails.retention_days', 90);

        if ($days < 1) {
            $days = 90;
        }

        $cutoff = Carbon::now()->subDays($days);

        $count = SentEmail::olderThan($cutoff)->delete();

        $this->info("{$count} verzonden e-mail(s) ouder dan {$days} dagen verwijderd.");

        return self::SUCCESS;
    }
}
