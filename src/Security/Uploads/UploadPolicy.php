<?php

namespace Dashed\DashedCore\Security\Uploads;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Instellingen voor uploads door bezoekers zonder paneelrechten, uit
 * config('dashed-core.uploads'). Aanleiding: op rechargehamster uploadde een
 * gast op 15-11-2026 ~150 probe-bestanden (svg, html, GIF/pdf-polyglots) via
 * een gesigneerde upload-URL die Livewire aan elke bezoeker uitdeelt.
 *
 * Modus (guard_mode):
 *  - off:     niets extra;
 *  - log:     de poort en de typelijst loggen overtredingen maar blokkeren
 *             niet (standaard, om eerst te zien welke componenten een project
 *             gebruikt); svg/html/xml en uitvoerbare bestanden worden wél geweigerd;
 *  - enforce: niet-aangemelde componenten krijgen een 403 en alleen de
 *             typelijst mag erdoor.
 *
 * Paneelgebruikers vallen er altijd buiten: voor hen gelden alleen de
 * project-regels en SafeUploadedFile.
 */
class UploadPolicy
{
    public const MODE_OFF = 'off';

    public const MODE_LOG = 'log';

    public const MODE_ENFORCE = 'enforce';

    public const DEFAULT_GUEST_MIMES = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'heic', 'heif', 'avif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];

    /** Nooit voor gasten, ook niet in modus log: kan in een browser script draaien of is uitvoerbaar. */
    public const DEFAULT_GUEST_BLOCKED_EXTENSIONS = [
        'svg', 'svgz', 'html', 'htm', 'xhtml', 'shtml', 'xht', 'xml', 'xsl', 'xslt', 'mht', 'mhtml',
        'js', 'mjs', 'swf', 'hta', 'jar', 'exe', 'dll', 'msi', 'bat', 'cmd', 'com', 'scr', 'ps1', 'vbs',
    ];

    public static function mode(): string
    {
        $mode = strtolower((string) config('dashed-core.uploads.guard_mode', self::MODE_LOG));

        return in_array($mode, [self::MODE_OFF, self::MODE_LOG, self::MODE_ENFORCE], true) ? $mode : self::MODE_LOG;
    }

    public static function enforcing(): bool
    {
        return static::mode() === self::MODE_ENFORCE;
    }

    public static function active(): bool
    {
        return static::mode() !== self::MODE_OFF;
    }

    /**
     * Zelfde maatstaf als Dashed\DashedCore\Models\User::canAccessPanel().
     */
    public static function isPanelUser(?Authenticatable $user): bool
    {
        if (! $user) {
            return false;
        }

        return method_exists($user, 'hasPanelAccess') && $user->hasPanelAccess();
    }

    /**
     * @return array<int, string>
     */
    public static function guestMimes(): array
    {
        return static::normalize((array) config('dashed-core.uploads.guest_mimes', self::DEFAULT_GUEST_MIMES));
    }

    /**
     * @return array<int, string>
     */
    public static function blockedGuestExtensions(): array
    {
        return static::normalize((array) config('dashed-core.uploads.guest_blocked_extensions', self::DEFAULT_GUEST_BLOCKED_EXTENSIONS));
    }

    public static function guestMaxKilobytes(): ?int
    {
        $max = config('dashed-core.uploads.guest_max_kb');

        return $max ? (int) $max : null;
    }

    /**
     * @return array<int, class-string>
     */
    public static function guestComponents(): array
    {
        return array_values(array_filter((array) config('dashed-core.uploads.guest_components', [])));
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, string>
     */
    protected static function normalize(array $values): array
    {
        return array_values(array_unique(array_filter(array_map(fn ($value) => strtolower(trim((string) $value, " .\t\n")), $values))));
    }
}
