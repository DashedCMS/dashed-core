<?php

namespace Dashed\DashedCore\Commands;

use Throwable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Dashed\DashedCore\Models\Report;
use Dashed\DashedCore\Mail\ScheduledReportMail;

/**
 * Verstuurt de ingeplande rapporten die vandaag aan de beurt zijn. Draait
 * elke ochtend; `last_sent_at` voorkomt een tweede mail op dezelfde dag als
 * de scheduler het commando vaker start.
 */
class SendScheduledReportsCommand extends Command
{
    protected $signature = 'dashed:send-scheduled-reports {--report= : Eén rapport nu versturen, ook als het niet aan de beurt is}';

    protected $description = 'Send scheduled reports to their recipients';

    public static function hasWork(): bool
    {
        return rescue(fn () => Report::query()->where('schedule', '!=', 'none')->exists(), false, false);
    }

    public function handle(): int
    {
        $query = Report::query();
        $forced = $this->option('report');
        $forced ? $query->whereKey($forced) : $query->where('schedule', '!=', 'none');

        $sent = 0;
        foreach ($query->get() as $report) {
            if (! $forced && ! $report->isDue()) {
                continue;
            }

            $recipients = $report->validRecipients();
            if ($recipients === []) {
                continue;
            }

            try {
                Mail::to($recipients)->send(new ScheduledReportMail($report, $report->run()));
                $report->forceFill(['last_sent_at' => now()])->save();
                $sent++;
            } catch (Throwable $e) {
                report($e);
                $this->error($report->name.': '.$e->getMessage());
            }
        }

        $this->info('Sent: '.$sent);

        return self::SUCCESS;
    }
}
