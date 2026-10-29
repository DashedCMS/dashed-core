<?php

namespace Dashed\DashedCore\Classes\Launch;

use Closure;
use Illuminate\Http\Request;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Classes\SystemHealth;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Classes\TrustedProxies;

/**
 * "Je winkel is 9 van 14 klaar": wat er voor een livegang geregeld moet zijn,
 * elk punt met een link naar het scherm waar het geregeld wordt. Een punt is
 * automatisch (een check die true of false geeft) of handmatig (iets dat
 * buiten het CMS gebeurt, zoals SPF en DKIM of de sitemap indienen bij
 * Google: dat vinkt een beheerder zelf af). Pakketten melden hun eigen punten
 * aan met extend(), zoals bij de Beveiligingscheck en de systeembalk.
 *
 * Elk punt zegt wie het regelt (`owner`: een beheerder in het CMS, elke
 * beheerder zelf, of de ontwikkelaar op de server of in de DNS) en een open
 * automatisch punt zegt wat er precies mist (`problems`, bijvoorbeeld welke
 * beheerders nog geen tweestapsverificatie hebben). Bij een afgevinkt
 * handmatig punt staat wie het afvinkte en wanneer.
 *
 * Afgevinkte punten en het moment van livegang staan als instelling op de
 * eerste site, want de checklist geldt voor de installatie en niet per site.
 */
class LaunchChecklist
{
    public const CHECKED_SETTING = 'launch_checklist_checked';

    public const CHECKED_BY_SETTING = 'launch_checklist_checked_by';

    public const LAUNCHED_SETTING = 'launch_completed_at';

    /** @var array<string, Closure(): array<int, array<string, mixed>>> */
    protected static array $extensions = [];

    /**
     * @param  Closure(): array<int, array{key: string, label: string, detail?: string, owner?: string, url?: ?string, check?: ?Closure, problems?: ?Closure, manual?: bool}>  $items
     */
    public static function extend(Closure $items, string $name): void
    {
        static::$extensions[$name] = $items;
    }

    public static function flushExtensions(): void
    {
        static::$extensions = [];
    }

    /**
     * @return array<int, array{key: string, label: string, detail: string, owner: string, url: ?string, manual: bool, done: bool, problems: array<int, string>, checked_by: ?array{name: string, at: string}}>
     */
    public static function items(?Request $request = null): array
    {
        $request ??= request();
        $checked = static::checked();
        $checkedBy = static::checkedBy();

        $definitions = static::coreItems($request);
        foreach (static::$extensions as $extension) {
            foreach ((array) rescue(fn () => $extension(), [], report: true) as $item) {
                if (is_array($item) && isset($item['key'], $item['label'])) {
                    $definitions[] = $item;
                }
            }
        }

        return array_map(function (array $item) use ($checked, $checkedBy) {
            $manual = (bool) ($item['manual'] ?? ! isset($item['check']));
            $done = $manual
                ? in_array($item['key'], $checked, true)
                : (bool) rescue(fn () => ($item['check'])(), false, report: false);
            $problems = ! $done && isset($item['problems'])
                ? array_values(array_filter((array) rescue(fn () => ($item['problems'])(), [], report: false), 'is_string'))
                : [];

            return [
                'key' => $item['key'],
                'label' => $item['label'],
                'detail' => $item['detail'] ?? '',
                'owner' => $item['owner'] ?? '',
                'url' => $item['url'] ?? null,
                'manual' => $manual,
                'done' => $done,
                'problems' => $problems,
                'checked_by' => $manual && $done ? ($checkedBy[$item['key']] ?? null) : null,
            ];
        }, $definitions);
    }

    /**
     * @return array{done: int, total: int}
     */
    public static function progress(?Request $request = null): array
    {
        $items = static::items($request);

        return ['done' => count(array_filter($items, fn ($item) => $item['done'])), 'total' => count($items)];
    }

    /**
     * @return array<int, string>
     */
    public static function checked(): array
    {
        $value = Customsetting::get(self::CHECKED_SETTING, Sites::getFirstSite()['id'] ?? null, '[]');
        $decoded = is_array($value) ? $value : json_decode((string) $value, true);

        return array_values(array_filter((array) $decoded, 'is_string'));
    }

    /**
     * Wie elk handmatig punt afvinkte en wanneer, op sleutel.
     *
     * @return array<string, array{name: string, at: string}>
     */
    public static function checkedBy(): array
    {
        $value = Customsetting::get(self::CHECKED_BY_SETTING, Sites::getFirstSite()['id'] ?? null, '{}');
        $decoded = is_array($value) ? $value : json_decode((string) $value, true);

        return array_filter((array) $decoded, fn ($entry) => is_array($entry) && isset($entry['name'], $entry['at']));
    }

    public static function toggle(string $key): void
    {
        $siteId = Sites::getFirstSite()['id'] ?? null;
        $checked = static::checked();
        $checkedBy = static::checkedBy();

        if (in_array($key, $checked, true)) {
            $checked = array_values(array_diff($checked, [$key]));
            unset($checkedBy[$key]);
        } else {
            $checked = [...$checked, $key];
            $checkedBy[$key] = ['name' => static::userLabel(auth()->user()) ?? __('onbekend'), 'at' => now()->toDateTimeString()];
        }

        Customsetting::set(self::CHECKED_SETTING, json_encode($checked), $siteId);
        Customsetting::set(self::CHECKED_BY_SETTING, json_encode($checkedBy), $siteId);
    }

    public static function launchedAt(): ?string
    {
        $value = Customsetting::get(self::LAUNCHED_SETTING, Sites::getFirstSite()['id'] ?? null);

        return filled($value) ? (string) $value : null;
    }

    public static function markLaunched(bool $launched = true): void
    {
        Customsetting::set(self::LAUNCHED_SETTING, $launched ? now()->toDateTimeString() : '', Sites::getFirstSite()['id'] ?? null);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected static function coreItems(Request $request): array
    {
        $siteId = Sites::getFirstSite()['id'] ?? null;
        $settingsUrl = fn (string $class) => rescue(fn () => $class::getUrl(), null, report: false);
        $health = collect(rescue(fn () => SystemHealth::run($request), [], report: false))->keyBy('key');

        return [
            [
                'key' => 'logo',
                'label' => __('Logo ingesteld'),
                'owner' => __('Een beheerder, in het CMS'),
                'url' => $settingsUrl(\Dashed\DashedCore\Filament\Pages\Settings\GeneralSettingsPage::class),
                'check' => fn () => filled(Customsetting::get('site_logo', $siteId)),
            ],
            [
                'key' => 'mail_sender',
                'label' => __('Afzenderadres voor mails'),
                'owner' => __('Een beheerder, in het CMS'),
                'url' => $settingsUrl(\Dashed\DashedCore\Filament\Pages\Settings\GeneralSettingsPage::class),
                'check' => fn () => filter_var(Customsetting::get('site_from_email', $siteId), FILTER_VALIDATE_EMAIL) !== false,
            ],
            [
                'key' => 'spf_dkim',
                'label' => __('SPF en DKIM voor het afzenderdomein'),
                'detail' => __('Controleer bij de mailprovider (Postmark) dat SPF en DKIM voor het domein van het afzenderadres op groen staan, anders belanden de ordermails in de spam.'),
                'owner' => __('De ontwikkelaar, in de DNS van het domein'),
                'manual' => true,
            ],
            [
                'key' => 'legal_pages',
                'label' => __('Algemene voorwaarden en privacyverklaring'),
                'detail' => __('Een gepubliceerde pagina met "voorwaarden" en een met "privacy" in de slug.'),
                'owner' => __('Een beheerder, in het CMS'),
                'url' => static::pagesUrl(),
                'check' => fn () => static::pageWithSlugExists('voorwaarden') && static::pageWithSlugExists('privacy'),
                'problems' => fn () => array_values(array_filter([
                    static::pageWithSlugExists('voorwaarden') ? null : __('Geen gepubliceerde pagina met "voorwaarden" in de slug'),
                    static::pageWithSlugExists('privacy') ? null : __('Geen gepubliceerde pagina met "privacy" in de slug'),
                ])),
            ],
            [
                'key' => 'mfa_all',
                'label' => __('Tweestapsverificatie voor elke beheerder'),
                'detail' => __('Elke beheerder stelt dit zelf in: bij de eerstvolgende keer inloggen vraagt het CMS erom en kan hij pas verder als het ingesteld is. Logt iemand nooit meer in, zet zijn rol dan op klant of verwijder het account.'),
                'owner' => __('Elke beheerder zelf, bij het inloggen'),
                'url' => rescue(fn () => \Dashed\DashedCore\Filament\Resources\UserResource::getUrl(), null, report: false),
                'check' => fn () => ! static::usersWithoutMfaQuery()->exists(),
                'problems' => fn () => static::usersWithoutMfaQuery()
                    ->orderBy('email')
                    ->get()
                    ->map(fn ($user) => __('Nog niet ingesteld: :wie (:rol)', ['wie' => static::userLabel($user), 'rol' => $user->role]))
                    ->all(),
            ],
            [
                'key' => 'app_url',
                'label' => __('APP_URL wijst naar het echte domein, over https'),
                'detail' => __('Staat in de .env op de server.'),
                'owner' => __('De ontwikkelaar, op de server'),
                'check' => fn () => str_starts_with((string) config('app.url'), 'https://')
                    && ($health['app_url']['status'] ?? SystemHealth::OK) === SystemHealth::OK,
            ],
            [
                'key' => 'trusted_proxies',
                'label' => __('Vertrouwde proxy ingesteld (achter Cloudflare)'),
                'detail' => __('DASHED_TRUSTED_PROXIES in de .env, alleen nodig als de site achter een proxy staat. Zonder deze instelling ziet het CMS bij elke bezoeker het adres van de proxy in plaats van het echte adres. De site werkt dan gewoon, maar de IP-lijst van het CMS, het inloglogboek, de meldingen over een login vanaf een nieuw adres en de verzoeklimieten (iedereen deelt dan één teller) kloppen niet.'),
                'owner' => __('De ontwikkelaar, op de server'),
                'check' => fn () => TrustedProxies::configured() !== []
                    || ! ($request->headers->has('X-Forwarded-For') || $request->headers->has('CF-Connecting-IP')),
                'problems' => fn () => [
                    __('Dit verzoek kwam via een proxy binnen, het CMS ziet nu :ip als adres van iedere bezoeker.', ['ip' => (string) $request->ip()]),
                ],
            ],
            [
                'key' => 'scheduler',
                'label' => __('Scheduler draait'),
                'detail' => __('Een cronregel op de server die elke minuut php artisan schedule:run draait. Zonder scheduler gaan herinneringen, opruimrondes en synchronisaties niet.'),
                'owner' => __('De ontwikkelaar, op de server'),
                'url' => $settingsUrl(\Dashed\DashedCore\Filament\Pages\Settings\SystemStatusPage::class),
                'check' => fn () => ($health['scheduler']['status'] ?? null) === SystemHealth::OK,
                'problems' => fn () => array_values(array_filter([(string) ($health['scheduler']['detail'] ?? '')])),
            ],
            [
                'key' => 'sitemap_submitted',
                'label' => __('Sitemap ingediend bij Google Search Console'),
                'detail' => __('Dien :url in bij Search Console.', ['url' => url('sitemap.xml')]),
                'owner' => __('Een beheerder of de marketeer, in Google Search Console'),
                'manual' => true,
            ],
            [
                'key' => 'error_pages',
                'label' => __('404-pagina in de huisstijl'),
                'detail' => __('Open een adres dat niet bestaat en kijk of de pagina in de stijl van de site staat.'),
                'owner' => __('Een beheerder, in de browser'),
                'manual' => true,
            ],
        ];
    }

    protected static function panelUserQuery()
    {
        $model = config('auth.providers.users.model') ?: \Dashed\DashedCore\Models\User::class;

        return $model::query()->whereNotNull('role')->where('role', '!=', 'customer');
    }

    protected static function usersWithoutMfaQuery()
    {
        return static::panelUserQuery()
            ->where(fn ($q) => $q->whereNull('app_authentication_secret')->orWhere('app_authentication_secret', ''))
            ->where(fn ($q) => $q->whereNull('has_email_authentication')->orWhere('has_email_authentication', false));
    }

    protected static function userLabel($user): ?string
    {
        if (! $user) {
            return null;
        }

        $name = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));

        return $name !== '' ? $name.' <'.$user->email.'>' : (string) $user->email;
    }

    protected static function pageWithSlugExists(string $needle): bool
    {
        $page = collect(cms()->builder('routeModels'))->firstWhere('class', 'Dashed\\DashedPages\\Models\\Page');
        $class = $page['class'] ?? null;

        if (! $class || ! class_exists($class)) {
            return false;
        }

        return $class::query()->where('slug', 'LIKE', '%'.$needle.'%')->where('public', true)->exists();
    }

    protected static function pagesUrl(): ?string
    {
        return rescue(fn () => \Filament\Facades\Filament::getPanel('dashed')->getModelResource('Dashed\\DashedPages\\Models\\Page')::getUrl(), null, report: false);
    }

    public static function flush(): void
    {
        static::$extensions = [];
    }
}
