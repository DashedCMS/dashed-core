<?php

namespace Dashed\DashedCore\Classes;

use Dashed\DashedCore\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Dashed\DashedCore\Models\LoginAttempt;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Mail\CmsFailedLoginMail;
use Dashed\DashedCore\Mail\CmsLoginFromNewIpMail;

/**
 * Beveiligingsmeldingen per mail, afgeleid van het inloglogboek.
 *
 * Twee gebeurtenissen: een beheerder logt in vanaf een IP-adres waarvandaan
 * dat account nog nooit is ingelogd, en een mislukte inlogpoging op een
 * beheerdersaccount (wachtwoord of MFA-code). Het logboek zelf is de bron van
 * wat "bekend" is: een adres is bekend zodra er een eerdere geslaagde poging
 * van dezelfde gebruiker vanaf dat adres in staat. De allereerste login van
 * een account (geen enkele eerdere geslaagde poging) wordt niet gemeld, want
 * dan is elk adres nieuw; die login is wel meteen het eerste bekende adres.
 *
 * Mislukte pogingen worden per account hooguit een keer per kwartier gemeld,
 * anders loopt de inbox vol bij een aanval, terwijl elke poging gewoon in het
 * logboek blijft staan. Klantaccounts tellen niet mee: alleen wie via het
 * paneel moet inloggen (User::mustLoginViaPanel()).
 *
 * Ontvangers: staat SECURITY_ALERT_RECIPIENTS in .env, dan uitsluitend die
 * adressen; de lijst in het CMS telt dan niet mee, zodat iemand met toegang
 * tot het CMS de meldingen niet kan omleiden of uitzetten voor zichzelf.
 * Zonder .env-adressen: de adressen bij Instellingen, Beveiliging, en zonder
 * die alle superadmins. De schakelaar staat standaard aan. Beide instellingen
 * staan op de eerste site, om dezelfde reden als [[CmsIpAllowlist]].
 */
class SecurityAlerts
{
    public const SETTING_ENABLED = 'security_alerts_enabled';

    public const SETTING_EMAILS = 'security_alert_emails';

    /** Bij elke beheerderslogin mailen, niet alleen bij een nieuw IP-adres. */
    public const SETTING_EVERY_LOGIN = 'security_alert_every_login';

    public const FAILED_LOGIN_COOLDOWN_MINUTES = 15;

    public const TYPE_LOGIN = 'login';

    public const TYPE_FAILED_LOGIN = 'failed_login';

    public const TYPE_PASSWORD_RESET = 'password_reset';

    public const TYPE_ADMIN_ACTION = 'admin_action';

    /** Geldvelden van een product gewijzigd door een beheerder; apart van de beheeracties, want dat gebeurt vaak en bewust. */
    public const TYPE_PRICE_CHANGE = 'price_change';

    public const TYPE_IP_ALLOWLIST = 'ip_allowlist';

    /**
     * De soorten meldingen, elk apart aan of uit te zetten en met eigen
     * ontvangers (Instellingen, Beveiliging). Sleutel => [label, uitleg].
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function types(): array
    {
        return [
            self::TYPE_LOGIN => [__('Login van een beheerder'), __('Bij een IP-adres dat voor dat account nieuw is, of bij elke login. Gaat ook naar de beheerder zelf, met de vergrendellink.')],
            self::TYPE_FAILED_LOGIN => [__('Mislukte inlogpoging'), __('Fout wachtwoord of foute MFA-code op een beheerdersaccount, hooguit een per kwartier per account.')],
            self::TYPE_PASSWORD_RESET => [__('Wachtwoord-reset aangevraagd'), __('Elk reset-verzoek op een beheerdersaccount, zonder de link.')],
            self::TYPE_ADMIN_ACTION => [__('Beheeracties'), __('Gewijzigde beveiligingsinstellingen, betaalmethodes, te veel handmatige betalingen, foute pincodes.')],
            self::TYPE_PRICE_CHANGE => [__('Prijswijzigingen'), __('Een beheerder wijzigt een prijs, inkoopprijs of btw-tarief van een product; een drastische verlaging wordt apart genoemd.')],
            self::TYPE_IP_ALLOWLIST => [__('IP-lijst gewijzigd'), __('Elke wijziging van de IP-lijst, ook vanaf de commandoregel.')],
        ];
    }

    public static function typeEnabledSetting(string $type): string
    {
        return 'security_alert_' . $type . '_enabled';
    }

    public static function typeEmailsSetting(string $type): string
    {
        return 'security_alert_' . $type . '_emails';
    }

    /** Aan als de hoofdschakelaar aan staat en de soort niet is uitgezet. */
    public static function typeEnabled(string $type): bool
    {
        if (! self::enabled()) {
            return false;
        }

        $value = Customsetting::get(self::typeEnabledSetting($type), self::siteId(), '1');

        if ($value === null || $value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    /**
     * De eigen adressen van een soort; leeg betekent de algemene lijst.
     *
     * @return array<int, string>
     */
    public static function typeEmails(string $type): array
    {
        return self::parseEmails((string) Customsetting::get(self::typeEmailsSetting($type), self::siteId(), ''));
    }

    /**
     * Ontvangers van een soort: .env gaat voor alles (zie recipients()),
     * dan de adressen van de soort, dan de algemene lijst, dan de
     * superadmins.
     *
     * @return array<int, string>
     */
    public static function recipientsFor(string $type): array
    {
        if ($fromEnv = self::envRecipients()) {
            return $fromEnv;
        }

        if ($own = self::typeEmails($type)) {
            return $own;
        }

        return self::recipients();
    }

    /**
     * Verstuurt een melding van een soort naar de ontvangers van die soort,
     * per ontvanger een eigen mailable (zie CmsIpAllowlist). Doet niets als
     * de soort of de hoofdschakelaar uit staat, tenzij $force: dat is voor
     * de melding over het uitzetten zelf.
     */
    public static function send(string $type, callable $mail, bool $force = false): void
    {
        if ($force ? ! self::enabled() : ! self::typeEnabled($type)) {
            return;
        }

        foreach (self::recipientsFor($type) as $recipient) {
            Mail::to($recipient)->queue($mail());
        }
    }

    public static function siteId(): string
    {
        return (string) Sites::getFirstSite()['id'];
    }

    public static function enabled(): bool
    {
        $value = Customsetting::get(self::SETTING_ENABLED, self::siteId(), '1');

        if ($value === null || $value === '') {
            return true;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL);
    }

    public static function everyLogin(): bool
    {
        return filter_var(Customsetting::get(self::SETTING_EVERY_LOGIN, self::siteId(), '0'), FILTER_VALIDATE_BOOL);
    }

    /**
     * @return array<int, string>
     */
    public static function configuredEmails(): array
    {
        return self::parseEmails((string) Customsetting::get(self::SETTING_EMAILS, self::siteId(), ''));
    }

    /**
     * @return array<int, string>
     */
    protected static function parseEmails(string $raw): array
    {
        $emails = [];

        foreach (preg_split('/[\s,;]+/', $raw) ?: [] as $email) {
            $email = strtolower(trim($email));

            if ($email !== '' && ! in_array($email, $emails, true)) {
                $emails[] = $email;
            }
        }

        return $emails;
    }

    /**
     * Adressen uit .env (SECURITY_ALERT_RECIPIENTS, komma-gescheiden).
     *
     * @return array<int, string>
     */
    public static function envRecipients(): array
    {
        $emails = [];

        foreach ((array) config('dashed-core.security_alert_recipients', []) as $email) {
            $email = strtolower(trim((string) $email));

            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && ! in_array($email, $emails, true)) {
                $emails[] = $email;
            }
        }

        return $emails;
    }

    /**
     * Staan er adressen in .env, dan is de lijst in het CMS niet leidend.
     */
    public static function recipientsLockedByEnv(): bool
    {
        return self::envRecipients() !== [];
    }

    /**
     * @return array<int, string>
     */
    public static function recipients(): array
    {
        if ($fromEnv = self::envRecipients()) {
            return $fromEnv;
        }

        $configured = self::configuredEmails();

        if ($configured) {
            return $configured;
        }

        return User::query()
            ->where('role', 'superadmin')
            ->whereNotNull('email')
            ->orderBy('id')
            ->pluck('email')
            ->map(fn ($email) => strtolower((string) $email))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Aangeroepen vanuit LoginAttempt::record(), binnen rescue(): het mailen
     * mag het inloggen nooit tegenhouden.
     */
    public static function afterAttempt(LoginAttempt $attempt): void
    {
        if (! self::enabled()) {
            return;
        }

        if ($attempt->result === LoginAttempt::RESULT_SUCCESS && $attempt->user_id) {
            self::handleSuccess($attempt);

            return;
        }

        if (in_array($attempt->result, [LoginAttempt::RESULT_FAILED, LoginAttempt::RESULT_FAILED_MFA], true) && $attempt->email) {
            self::handleFailure($attempt);
        }
    }

    /**
     * Standaard alleen bij een IP-adres dat voor dit account nieuw is; met
     * de schakelaar "bij elke login" aan bij elke login, met in de mail of
     * het adres bekend is. De mail gaat ook naar de gebruiker zelf, want die
     * weet als enige zeker of hij het was, en bevat de vergrendellink.
     */
    protected static function handleSuccess(LoginAttempt $attempt): void
    {
        $user = $attempt->user;

        if (! $user || ! $user->mustLoginViaPanel() || ! self::typeEnabled(self::TYPE_LOGIN)) {
            return;
        }

        $earlier = LoginAttempt::query()
            ->where('user_id', $attempt->user_id)
            ->where('result', LoginAttempt::RESULT_SUCCESS)
            ->where('id', '<', $attempt->id);

        $firstEver = ! (clone $earlier)->exists();
        $knownIp = $attempt->ip && (clone $earlier)->where('ip', $attempt->ip)->exists();
        $newIp = ! $firstEver && ! $knownIp;

        // Eerste login ooit: niets om mee te vergelijken, en vanaf nu bekend.
        if (! $newIp && ! self::everyLogin()) {
            return;
        }

        $mail = fn () => new CmsLoginFromNewIpMail(
            email: (string) $user->email,
            name: trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: ($user->name ?? ''),
            ip: (string) $attempt->ip,
            userAgent: (string) $attempt->user_agent,
            at: $attempt->created_at?->format('d-m-Y H:i') ?? now()->format('d-m-Y H:i'),
            newIp: $newIp,
            lockUrl: LockUserAction::notMeUrl($user),
            allowlistName: CmsIpAllowlist::nameFor((string) $attempt->ip),
        );

        $recipients = self::recipientsFor(self::TYPE_LOGIN);

        if ($user->email && ! in_array(strtolower($user->email), $recipients, true)) {
            $recipients[] = strtolower($user->email);
        }

        foreach ($recipients as $recipient) {
            Mail::to($recipient)->queue($mail());
        }
    }

    protected static function handleFailure(LoginAttempt $attempt): void
    {
        $email = strtolower(trim((string) $attempt->email));

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user || ! $user->mustLoginViaPanel() || ! self::typeEnabled(self::TYPE_FAILED_LOGIN)) {
            return;
        }

        // Cache::add() is atomair: de eerste poging binnen het venster mailt,
        // de rest niet.
        $key = 'dashed.security-alert.failed-login.' . $user->getKey();

        if (! Cache::add($key, true, now()->addMinutes(self::FAILED_LOGIN_COOLDOWN_MINUTES))) {
            return;
        }

        self::send(self::TYPE_FAILED_LOGIN, fn () => new CmsFailedLoginMail(
            email: (string) $user->email,
            ip: (string) $attempt->ip,
            userAgent: (string) $attempt->user_agent,
            at: $attempt->created_at?->format('d-m-Y H:i') ?? now()->format('d-m-Y H:i'),
            viaMfa: $attempt->result === LoginAttempt::RESULT_FAILED_MFA,
            cooldownMinutes: self::FAILED_LOGIN_COOLDOWN_MINUTES,
        ));
    }
}
