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
        $raw = (string) Customsetting::get(self::SETTING_EMAILS, self::siteId(), '');

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

        if (! $user || ! $user->mustLoginViaPanel()) {
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

        $recipients = self::recipients();

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

        if (! $user || ! $user->mustLoginViaPanel()) {
            return;
        }

        // Cache::add() is atomair: de eerste poging binnen het venster mailt,
        // de rest niet.
        $key = 'dashed.security-alert.failed-login.' . $user->getKey();

        if (! Cache::add($key, true, now()->addMinutes(self::FAILED_LOGIN_COOLDOWN_MINUTES))) {
            return;
        }

        self::send(fn () => new CmsFailedLoginMail(
            email: (string) $user->email,
            ip: (string) $attempt->ip,
            userAgent: (string) $attempt->user_agent,
            at: $attempt->created_at?->format('d-m-Y H:i') ?? now()->format('d-m-Y H:i'),
            viaMfa: $attempt->result === LoginAttempt::RESULT_FAILED_MFA,
            cooldownMinutes: self::FAILED_LOGIN_COOLDOWN_MINUTES,
        ));
    }

    /**
     * Per ontvanger een eigen mailable, anders stapelen de adressen op
     * (zie CmsIpAllowlist::notifySuperadmins()).
     */
    protected static function send(callable $mail): void
    {
        foreach (self::recipients() as $recipient) {
            Mail::to($recipient)->queue($mail());
        }
    }
}
