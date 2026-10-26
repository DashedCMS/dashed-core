<?php

namespace Dashed\DashedCore\Classes\Launch;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
 * Afgevinkte punten en het moment van livegang staan als instelling op de
 * eerste site, want de checklist geldt voor de installatie en niet per site.
 */
class LaunchChecklist
{
    public const CHECKED_SETTING = 'launch_checklist_checked';

    public const LAUNCHED_SETTING = 'launch_completed_at';

    /** @var array<string, Closure(): array<int, array<string, mixed>>> */
    protected static array $extensions = [];

    /**
     * @param  Closure(): array<int, array{key: string, label: string, detail?: string, url?: ?string, check?: ?Closure, manual?: bool}>  $items
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
     * @return array<int, array{key: string, label: string, detail: string, url: ?string, manual: bool, done: bool}>
     */
    public static function items(?Request $request = null): array
    {
        $request ??= request();
        $checked = static::checked();

        $definitions = static::coreItems($request);
        foreach (static::$extensions as $extension) {
            foreach ((array) rescue(fn () => $extension(), [], report: true) as $item) {
                if (is_array($item) && isset($item['key'], $item['label'])) {
                    $definitions[] = $item;
                }
            }
        }

        return array_map(function (array $item) use ($checked) {
            $manual = (bool) ($item['manual'] ?? ! isset($item['check']));

            return [
                'key' => $item['key'],
                'label' => $item['label'],
                'detail' => $item['detail'] ?? '',
                'url' => $item['url'] ?? null,
                'manual' => $manual,
                'done' => $manual
                    ? in_array($item['key'], $checked, true)
                    : (bool) rescue(fn () => ($item['check'])(), false, report: false),
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

    public static function toggle(string $key): void
    {
        $checked = static::checked();
        $checked = in_array($key, $checked, true)
            ? array_values(array_diff($checked, [$key]))
            : [...$checked, $key];

        Customsetting::set(self::CHECKED_SETTING, json_encode($checked), Sites::getFirstSite()['id'] ?? null);
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
                'url' => $settingsUrl(\Dashed\DashedCore\Filament\Pages\Settings\GeneralSettingsPage::class),
                'check' => fn () => filled(Customsetting::get('site_logo', $siteId)),
            ],
            [
                'key' => 'mail_sender',
                'label' => __('Afzenderadres voor mails'),
                'url' => $settingsUrl(\Dashed\DashedCore\Filament\Pages\Settings\GeneralSettingsPage::class),
                'check' => fn () => filter_var(Customsetting::get('site_from_email', $siteId), FILTER_VALIDATE_EMAIL) !== false,
            ],
            [
                'key' => 'spf_dkim',
                'label' => __('SPF en DKIM voor het afzenderdomein'),
                'detail' => __('Controleer bij de mailprovider (Postmark) dat SPF en DKIM voor het domein van het afzenderadres op groen staan, anders belanden de ordermails in de spam.'),
                'manual' => true,
            ],
            [
                'key' => 'legal_pages',
                'label' => __('Algemene voorwaarden en privacyverklaring'),
                'detail' => __('Een gepubliceerde pagina met "voorwaarden" en een met "privacy" in de slug.'),
                'url' => static::pagesUrl(),
                'check' => fn () => static::pageWithSlugExists('voorwaarden') && static::pageWithSlugExists('privacy'),
            ],
            [
                'key' => 'mfa_all',
                'label' => __('Tweestapsverificatie voor elke beheerder'),
                'url' => rescue(fn () => \Dashed\DashedCore\Filament\Resources\UserResource::getUrl(), null, report: false),
                'check' => fn () => ! static::panelUserQuery()
                    ->where(fn ($q) => $q->whereNull('app_authentication_secret')->orWhere('app_authentication_secret', ''))
                    ->where(fn ($q) => $q->whereNull('has_email_authentication')->orWhere('has_email_authentication', false))
                    ->exists(),
            ],
            [
                'key' => 'app_url',
                'label' => __('APP_URL wijst naar het echte domein, over https'),
                'detail' => __('Staat in de .env op de server.'),
                'check' => fn () => str_starts_with((string) config('app.url'), 'https://')
                    && ($health['app_url']['status'] ?? SystemHealth::OK) === SystemHealth::OK,
            ],
            [
                'key' => 'trusted_proxies',
                'label' => __('Vertrouwde proxy ingesteld (achter Cloudflare)'),
                'detail' => __('DASHED_TRUSTED_PROXIES in de .env, alleen nodig als de site achter een proxy staat.'),
                'check' => fn () => TrustedProxies::configured() !== []
                    || ! ($request->headers->has('X-Forwarded-For') || $request->headers->has('CF-Connecting-IP')),
            ],
            [
                'key' => 'scheduler',
                'label' => __('Scheduler draait'),
                'url' => $settingsUrl(\Dashed\DashedCore\Filament\Pages\Settings\SystemStatusPage::class),
                'check' => fn () => ($health['scheduler']['status'] ?? null) === SystemHealth::OK,
            ],
            [
                'key' => 'sitemap_submitted',
                'label' => __('Sitemap ingediend bij Google Search Console'),
                'detail' => __('Dien :url in bij Search Console.', ['url' => url('sitemap.xml')]),
                'manual' => true,
            ],
            [
                'key' => 'error_pages',
                'label' => __('404-pagina in de huisstijl'),
                'detail' => __('Open een adres dat niet bestaat en kijk of de pagina in de stijl van de site staat.'),
                'manual' => true,
            ],
        ];
    }

    protected static function panelUserQuery()
    {
        $model = config('auth.providers.users.model') ?: \Dashed\DashedCore\Models\User::class;

        return $model::query()->whereNotNull('role')->where('role', '!=', 'customer');
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
