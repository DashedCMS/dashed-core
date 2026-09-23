<?php

namespace Dashed\DashedCore\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Dashed\DashedCore\Models\Customsetting;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * Een of meer bewaakte beheeracties (instelling, prijs, betaalmethode,
 * handmatige betaling) zijn uitgevoerd. Zie AdminActionMonitor.
 *
 * De mail draagt een lijst acties en niet één actie, want wie het
 * instellingenscherm opslaat wijzigt vaak meerdere bewaakte instellingen
 * tegelijk. Wie, vanaf welk adres en wanneer geldt voor die acties samen en
 * staat daarom apart in $context in plaats van bij elke actie opnieuw.
 */
class AdminActionAlertMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    /** Bij één actie de actie zelf, bij meer het aantal. */
    public string $title;

    /**
     * De feiten zoals een lezer ze bij één melding verwacht: bij één actie
     * die van de actie plus de context, bij meer alleen de context.
     *
     * @var array<string, string>
     */
    public array $facts;

    /**
     * @param  array<int, array{title: string, facts: array<string, string>}>  $actions
     * @param  array<string, string>  $context
     */
    public function __construct(
        public array $actions,
        public array $context = [],
    ) {
        $this->actions = array_values($this->actions);

        $this->title = count($this->actions) === 1
            ? (string) ($this->actions[0]['title'] ?? '')
            : __(':aantal beheeracties', ['aantal' => count($this->actions)]);

        $this->facts = count($this->actions) === 1
            ? array_merge((array) ($this->actions[0]['facts'] ?? []), $this->context)
            : $this->context;
    }

    /**
     * @param  array<string, string>  $facts
     * @param  array<string, string>  $context
     */
    public static function single(string $title, array $facts, array $context = []): self
    {
        return new self([['title' => $title, 'facts' => $facts]], $context);
    }

    public function build(): self
    {
        return $this
            ->view('dashed-core::emails.admin-action-alert')
            ->subject(sprintf('[%s] %s', Customsetting::get('site_name', null, 'Dashed'), $this->title))
            ->from(Customsetting::get('site_from_email'), Customsetting::get('site_name'))
            ->with([
                'siteName' => Customsetting::get('site_name'),
            ]);
    }
}
