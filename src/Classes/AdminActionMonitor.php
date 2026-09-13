<?php

namespace Dashed\DashedCore\Classes;

use Illuminate\Support\Facades\Log;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Mail\AdminActionAlertMail;

/**
 * Beheeracties bewaken op model-events, onafhankelijk van welk scherm of
 * script de wijziging doet. Elke melding gaat als mail naar de ontvangers
 * van de beveiligingsmeldingen (SecurityAlerts::recipients(), dus met de
 * .env-vergrendeling) en als [admin-monitor] in het log, met veroorzaker,
 * IP en tijdstip erbij.
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
     * Bewust zonder "al geregistreerd"-vlag: een statische vlag overleeft de
     * herstart van de applicatie tussen twee tests, en dan hangt de listener
     * aan een dispatcher die niet meer bestaat.
     */
    public static function register(): void
    {
        Customsetting::saved(fn (Customsetting $setting) => self::settingSaved($setting));
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

        $old = $setting->getOriginal($field);
        $new = $setting->{$field};

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
     * @param  array<string, string>  $facts
     */
    public static function alert(string $title, array $facts, bool $force = false): void
    {
        if ($force ? ! SecurityAlerts::enabled() : ! SecurityAlerts::typeEnabled(SecurityAlerts::TYPE_ADMIN_ACTION)) {
            return;
        }

        $user = auth()->user();

        $facts = array_merge($facts, [
            __('Door') => $user ? ($user->email . ' (#' . $user->getKey() . ')') : __('systeem'),
            __('IP') => app()->runningInConsole() && ! app()->runningUnitTests() ? 'cli' : (string) request()->ip(),
            __('Tijd') => now()->format('d-m-Y H:i:s'),
        ]);

        Log::warning('[admin-monitor] ' . $title, $facts);

        rescue(fn () => SecurityAlerts::send(SecurityAlerts::TYPE_ADMIN_ACTION, fn () => new AdminActionAlertMail($title, $facts), force: $force), report: true);
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
