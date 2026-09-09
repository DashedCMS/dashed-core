<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\Classes\SecurityAlerts;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Eén regel per inlogpoging op het CMS: gelukt, mislukt (wachtwoord of
 * onbekend adres), mislukt op de MFA-code, uitgelogd, of geweigerd op IP
 * voordat er überhaupt een wachtwoord ingevuld kon worden.
 *
 * Alleen het CMS-paneel schrijft hierin, vanuit CmsLogin, de uitlogroute en
 * EnsureCmsIpAllowed. Webshopklanten loggen via dezelfde guard en dezelfde
 * users-tabel in, maar horen hier niet tussen; daarom hangt dit niet aan de
 * algemene auth-events.
 */
class LoginAttempt extends Model
{
    public const RESULT_SUCCESS = 'success';

    public const RESULT_FAILED = 'failed';

    public const RESULT_FAILED_MFA = 'failed_mfa';

    public const RESULT_LOGOUT = 'logout';

    public const RESULT_IP_BLOCKED = 'ip_blocked';

    public const RESULT_IDLE_LOGOUT = 'idle_logout';

    protected $table = 'dashed__login_attempts';

    protected $guarded = [];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::RESULT_SUCCESS => __('Gelukt'),
            self::RESULT_FAILED => __('Mislukt'),
            self::RESULT_FAILED_MFA => __('Mislukt op MFA-code'),
            self::RESULT_LOGOUT => __('Uitgelogd'),
            self::RESULT_IP_BLOCKED => __('Geweigerd op IP'),
            self::RESULT_IDLE_LOGOUT => __('Automatisch uitgelogd'),
        ];
    }

    public static function colors(): array
    {
        return [
            self::RESULT_SUCCESS => 'success',
            self::RESULT_FAILED => 'danger',
            self::RESULT_FAILED_MFA => 'danger',
            self::RESULT_LOGOUT => 'gray',
            self::RESULT_IP_BLOCKED => 'warning',
            self::RESULT_IDLE_LOGOUT => 'gray',
        ];
    }

    /**
     * Schrijft de regel weg en laat het inloggen zelf nooit klappen: een
     * ontbrekende tabel midden in een uitrol mag geen 500 op de inlogpagina
     * opleveren.
     */
    public static function record(string $result, ?string $email, ?User $user = null): ?static
    {
        $attempt = rescue(fn () => static::create([
            'result' => $result,
            'email' => $email ? mb_substr($email, 0, 255) : null,
            'user_id' => $user?->getKey(),
            'ip' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 1000) ?: null,
        ]), report: false);

        // De beveiligingsmeldingen (nieuw IP, mislukte poging op een
        // beheerdersaccount) hangen aan het logboek. Ook hier binnen rescue():
        // een mail die niet weg kan is geen reden om iemand niet in te loggen.
        if ($attempt) {
            rescue(fn () => SecurityAlerts::afterAttempt($attempt), report: true);
        }

        return $attempt ?: null;
    }
}
