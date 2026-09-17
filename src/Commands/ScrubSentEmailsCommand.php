<?php

namespace Dashed\DashedCore\Commands;

use Illuminate\Console\Command;
use Dashed\DashedCore\Models\SentEmail;
use Dashed\DashedCore\Classes\SentEmailScrubber;

/**
 * Haalt geheimen uit al opgeslagen verzonden mails: de body van mails uit de
 * achterhoudlijst wordt vervangen door de vaste tekst, en in alle andere
 * mails worden tokens in links weggehaald. De mailgegevens zelf zijn bij een
 * oude rij niet meer beschikbaar, dus een wachtwoord in een mail die niet op
 * de lijst staat kan hier niet meer herkend worden.
 */
class ScrubSentEmailsCommand extends Command
{
    protected $signature = 'dashed:scrub-sent-emails';

    protected $description = 'Haal wachtwoorden, codes en tokens uit het logboek van verzonden mails.';

    public function handle(): int
    {
        $withheld = 0;
        $scrubbed = 0;

        SentEmail::query()->select(['id', 'mailable_class', 'html_body', 'text_body'])->orderBy('id')->chunkById(200, function ($rows) use (&$withheld, &$scrubbed) {
            foreach ($rows as $row) {
                $data = $row->mailable_class ? ['__laravel_mailable' => $row->mailable_class] : [];

                if (SentEmailScrubber::withholdsBody($data)) {
                    if ($row->html_body === SentEmailScrubber::WITHHELD && $row->text_body === SentEmailScrubber::WITHHELD) {
                        continue;
                    }
                    $row->update(['html_body' => SentEmailScrubber::WITHHELD, 'text_body' => SentEmailScrubber::WITHHELD]);
                    $withheld++;

                    continue;
                }

                $html = SentEmailScrubber::scrub($row->html_body);
                $text = SentEmailScrubber::scrub($row->text_body);
                if ($html !== $row->html_body || $text !== $row->text_body) {
                    $row->update(['html_body' => $html, 'text_body' => $text]);
                    $scrubbed++;
                }
            }
        });

        $this->info(__(':achtergehouden mail(s) zonder body, :gescrubd mail(s) met tokens weggehaald.', ['achtergehouden' => $withheld, 'gescrubd' => $scrubbed]));

        return self::SUCCESS;
    }
}
