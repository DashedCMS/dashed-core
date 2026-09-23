<?php

namespace Dashed\DashedCore\Classes;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Mail\AdminActionAlertMail;

/**
 * Beheeracties bewaken op model-events, onafhankelijk van welk scherm of
 * script de wijziging doet. Elke melding gaat meteen als [admin-monitor] in
 * het log, met veroorzaker, IP en tijdstip erbij, en aan het eind van het
 * verzoek als mail naar de ontvangers van de beveiligingsmeldingen
 * (SecurityAlerts::recipientsFor(), dus met de .env-vergrendeling). Alle
 * acties van hetzelfde verzoek en dezelfde soort gaan in één mail: het
 * instellingenscherm slaat acht bewaakte instellingen tegelijk op, en dat
 * hoort geen acht mails te zijn.
 *
 * Wat bewaakt wordt staat in een register, zodat elk pakket zijn eigen
 * modellen kan aanmelden (dashed-ecommerce-core: geldvelden van producten,
 * betaalmethodes, handmatige betaalmarkeringen). dashed-core zelf bewaakt
 * de instellingen die de verdediging van het CMS bepalen: MFA, meldingen,
 * sessieduur, wachtwoord-reset. De IP-lijst krijgt alleen een regel in het
 * activiteitenlogboek, want CmsIpAllowlist mailt daar al zelf over.
 * Customsetting heeft bewust geen LogsActivity, dus dit is de enige plek
 * waar zo'n wijziging in dat logboek terechtkomt.
 */
class AdminActionMonitor
{
    /** Instellingen waarvan een wijziging een mail en een activity-regel geeft. */
    public const WATCHED_SETTINGS = '/^(mfa_|force_mfa$|security_alert|cms_idle_timeout|cms_session_max|cms_admin_password_reset|cms_password_reset)/';

    /** Instellingen die alleen een activity-regel krijgen. */
    public const LOGGED_SETTINGS = '/^cms_allowed_ips$/';

    /** @var array<int, string> */
    protected static array $watchedPatterns = [self::WATCHED_SETTINGS];

    /** @var array<int, string> */
    protected static array $loggedPatterns = [self::LOGGED_SETTINGS];

    /**
     * De meldingen van dit verzoek, per soort gebundeld tot flush().
     *
     * @var array<string, array{context: array<string, string>, recipients: array<int, string>, actions: array<int, array{title: string, facts: array<string, string>}>}>
     */
    protected static array $pending = [];

    protected static bool $flushScheduled = false;

    /**
     * Bewust zonder "al geregistreerd"-vlag: een statische vlag overleeft de
     * herstart van de applicatie tussen twee tests, en dan hangt de listener
     * aan een dispatcher die niet meer bestaat.
     */
    public static function register(): void
    {
        // Een verzoek begint met een lege bundel. Statische eigenschappen
        // overleven de herstart van de applicatie tussen twee tests, en een
        // blijven staan flush-vlag zou daar betekenen dat de volgende test
        // zijn terminating-haak nooit meer zet.
        self::$pending = [];
        self::$flushScheduled = false;

        Customsetting::saved(fn (Customsetting $setting) => self::settingSaved($setting));

        // Een wachtrij-werker eindigt nooit, dus terminating() komt daar pas
        // als de werker stopt; zonder dit bleef een melding uit een job uren
        // liggen.
        Queue::after(fn () => self::flush());
        Queue::failing(fn () => self::flush());
    }

    /**
     * Extra instellingen bewaken (regex op de naam), voor pakketten met eigen
     * beveiligingsinstellingen zoals fraudedrempels of captcha.
     */
    public static function watchSettings(string $pattern, bool $mail = true): void
    {
        if ($mail) {
            self::$watchedPatterns[] = $pattern;
        } else {
            self::$loggedPatterns[] = $pattern;
        }
    }

    public static function enabled(): bool
    {
        return SecurityAlerts::enabled();
    }

    protected static function settingSaved(Customsetting $setting): void
    {
        // Alleen wat een ingelogde gebruiker doet is een beheeractie; een
        // migratie of commando dat een instelling zet hoort hier niet.
        if (! auth()->check()) {
            return;
        }

        $name = (string) $setting->name;
        $watched = self::matches(self::$watchedPatterns, $name);

        if (! $watched && ! self::matches(self::$loggedPatterns, $name)) {
            return;
        }

        $field = $setting->wasChanged('json') ? 'json' : 'value';

        if (! $setting->wasRecentlyCreated && ! $setting->wasChanged($field)) {
            return;
        }

        $old = $setting->wasRecentlyCreated ? null : $setting->getOriginal($field);
        $new = $setting->{$field};

        // Alleen een echte verandering telt. Het instellingenscherm slaat elk
        // veld op, ook de lege: de eerste keer opslaan maakt rijen zonder
        // waarde aan, en null tegenover '' geldt voor Eloquent als gewijzigd.
        // Dat gaf een mail "Oud: leeg, Nieuw: leeg".
        if (self::stringify($old) === self::stringify($new)) {
            return;
        }

        rescue(fn () => activity()
            ->performedOn($setting)
            ->withProperties(['setting' => $name, 'site_id' => $setting->site_id, 'old' => $old, 'new' => $new])
            ->log('security:setting-changed'), report: false);

        if (! $watched) {
            return;
        }

        // De melding over het uitzetten van een meldingssoort gaat altijd,
        // anders zet iemand eerst de beheeractie-meldingen uit en daarna
        // ongemerkt de rest.
        self::alert(__('Instelling gewijzigd: :naam', ['naam' => $name]), [
            __('Instelling') => $name,
            __('Site') => (string) $setting->site_id,
            __('Oud') => self::stringify($old),
            __('Nieuw') => self::stringify($new),
        ], force: str_starts_with($name, 'security_alert'));
    }

    /**
     * Een melding versturen. Voegt veroorzaker, IP en tijdstip toe. Binnen
     * rescue(): een melding die niet weg kan mag de actie zelf nooit
     * tegenhouden.
     *
     * De mail gaat niet meteen de deur uit maar wacht tot het eind van het
     * verzoek (zie flush()): wie het instellingenscherm opslaat wijzigt vaak
     * meerdere bewaakte instellingen tegelijk, en dat hoort één mail te zijn
     * en geen stapel van acht. Het logboek krijgt elke actie wel meteen.
     *
     * @param  array<string, string>  $facts
     * @param  string|null  $type  de meldingssoort waar de schakelaar en de
     *                             ontvangers van gelden; standaard Beheeracties
     */
    public static function alert(string $title, array $facts, bool $force = false, ?string $type = null): void
    {
        $type ??= SecurityAlerts::TYPE_ADMIN_ACTION;

        if ($force ? ! SecurityAlerts::enabled() : ! SecurityAlerts::typeEnabled($type)) {
            return;
        }

        $context = self::context();

        Log::warning('[admin-monitor] ' . $title, array_merge($facts, $context));

        // Een bundel per soort: de ontvangers verschillen per soort, en of een
        // melding mee mag is hierboven al beslist.
        $key = $type;

        if (! isset(self::$pending[$key])) {
            // De ontvangers worden nu vastgelegd en niet pas bij flush(): wie
            // in ditzelfde verzoek de meldingen uitzet of omleidt, mag de
            // melding daarover niet alsnog tegenhouden.
            self::$pending[$key] = ['context' => $context, 'recipients' => SecurityAlerts::recipientsFor($type), 'actions' => []];
        }

        self::$pending[$key]['actions'][] = ['title' => $title, 'facts' => $facts];

        self::scheduleFlush();
    }

    /**
     * Wie, vanaf welk adres, wanneer. Geldt voor alle meldingen van hetzelfde
     * verzoek en staat daarom los van de feiten van een losse actie.
     *
     * @return array<string, string>
     */
    protected static function context(): array
    {
        $user = auth()->user();

        return [
            __('Door') => $user ? ($user->email . ' (#' . $user->getKey() . ')') : __('systeem'),
            __('IP') => app()->runningInConsole() && ! app()->runningUnitTests() ? 'cli' : (string) request()->ip(),
            __('Tijd') => now()->format('d-m-Y H:i:s'),
        ];
    }

    /**
     * De verzamelde meldingen versturen, één mail per soort. Draait aan het
     * eind van het verzoek, de artisan-opdracht of de wachtrij-job; is er
     * niets verzameld, dan doet dit niets.
     */
    public static function flush(): void
    {
        $bundles = self::$pending;

        self::$pending = [];
        self::$flushScheduled = false;

        foreach ($bundles as $bundle) {
            foreach ($bundle['recipients'] as $recipient) {
                rescue(fn () => Mail::to($recipient)->queue(new AdminActionAlertMail($bundle['actions'], $bundle['context'])), report: true);
            }
        }
    }

    /**
     * terminating() draait na het versturen van de response, en ook aan het
     * eind van een artisan-opdracht. Een wachtrij-werker blijft draaien, dus
     * daar hangt flush() in register() aan het eind van elke job.
     */
    protected static function scheduleFlush(): void
    {
        if (self::$flushScheduled) {
            return;
        }

        self::$flushScheduled = true;

        rescue(fn () => app()->terminating(fn () => self::flush()), report: false);
    }

    /**
     * @param  array<int, string>  $patterns
     */
    protected static function matches(array $patterns, string $name): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $name)) {
                return true;
            }
        }

        return false;
    }

    protected static function stringify(mixed $value): string
    {
        if ($value === null || $value === '') {
            return __('leeg');
        }

        return is_array($value) ? (string) json_encode($value, JSON_UNESCAPED_UNICODE) : (string) $value;
    }
}
