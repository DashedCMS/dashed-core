<?php

namespace Dashed\DashedCore\Classes\AnnouncementBars;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Dashed\DashedCore\Classes\Sites;
use Illuminate\Support\Facades\Schema;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Models\AnnouncementBar;
use Dashed\DashedCore\Classes\Caching\CacheInvalidator;

/**
 * Actiebalken bovenaan de site.
 *
 * Welke balk er staat beslist de server op tijd (actief, start, eind, type
 * pagina), en dat komt in de gecachte HTML. Daarom leegt
 * `dashed:announcement-bars:refresh-cache` elke minuut de responscache van een
 * site zodra een start- of eindmoment van een balk net voorbij is; het opslaan
 * van een balk leegt hem ook. De countdown en het onthouden van wegklikken
 * gebeuren in de browser, zodat de HTML voor elke bezoeker gelijk blijft.
 *
 * Standaard uit: per site aan te zetten met `announcement_bars_enabled`.
 */
class AnnouncementBars
{
    public const SETTING = 'announcement_bars_enabled';

    /** Cachesleutel met het moment van de vorige controle op start/eind. */
    public const LAST_CHECK_KEY = 'dashed:announcement-bars:last-check';

    public const DEFAULT_BACKGROUND = '#111827';

    public const DEFAULT_TEXT = '#ffffff';

    /** @return array<string, string> */
    public static function pageTypes(): array
    {
        return [
            'home' => __('Homepagina'),
            'page' => __('Overige pagina\'s'),
            'product' => __('Productpagina\'s'),
            'category' => __('Categoriepagina\'s'),
            'article' => __('Artikelen'),
            'other' => __('Overig (winkelwagen, afrekenen, account en dergelijke)'),
        ];
    }

    public static function enabled($siteId = null): bool
    {
        return (bool) Customsetting::get(self::SETTING, $siteId ?: Sites::getActive());
    }

    /** Het type van de pagina, af te leiden uit het model dat de pagina toont. */
    public static function pageTypeOf(mixed $model): string
    {
        if (! is_object($model)) {
            return 'other';
        }

        return match (class_basename($model)) {
            'Page' => ($model->is_home ?? false) ? 'home' : 'page',
            'Product', 'ProductGroup' => 'product',
            'ProductCategory' => 'category',
            'Article', 'ArticleCategory', 'ArticleAuthor' => 'article',
            default => 'other',
        };
    }

    /**
     * De balken die nu op deze pagina horen, in volgorde.
     *
     * @return Collection<int, AnnouncementBar>
     */
    public static function forPage(mixed $model = null, $siteId = null, ?Carbon $moment = null): Collection
    {
        $siteId = $siteId ?: Sites::getActive();

        if (! self::enabled($siteId) || ! self::tableExists()) {
            return collect();
        }

        $moment ??= Carbon::now();
        $type = self::pageTypeOf($model);

        return AnnouncementBar::query()
            ->where('active', true)
            ->where(fn ($q) => $q->whereNull('site_id')->orWhere('site_id', $siteId))
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $moment))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $moment))
            ->orderBy('sort')
            ->orderBy('id')
            ->get()
            ->filter(fn (AnnouncementBar $bar) => empty($bar->page_types) || in_array($type, (array) $bar->page_types, true))
            ->filter(fn (AnnouncementBar $bar) => $bar->textFor() !== '')
            ->values();
    }

    public static function render(mixed $model = null): string
    {
        return rescue(function () use ($model): string {
            $bars = self::forPage($model);

            if ($bars->isEmpty()) {
                return '';
            }

            return view('dashed-core::components.frontend.announcement-bars', [
                'bars' => $bars,
                'locale' => app()->getLocale(),
            ])->render();
        }, '', false);
    }

    /**
     * Leegt de responscache van elke site waar sinds de vorige controle een
     * balk begon of eindigde. Geeft de geleegde sites terug.
     *
     * @return array<int, string>
     */
    public static function flushPassedBoundaries(?Carbon $now = null): array
    {
        if (! self::tableExists()) {
            return [];
        }

        $now ??= Carbon::now();
        $last = Cache::get(self::LAST_CHECK_KEY);
        $since = $last ? Carbon::parse($last) : $now->copy()->subMinutes(5);
        Cache::forever(self::LAST_CHECK_KEY, $now->toIso8601String());

        $siteIds = AnnouncementBar::query()
            ->where('active', true)
            ->where(function ($q) use ($since, $now) {
                $q->whereBetween('starts_at', [$since, $now])
                    ->orWhereBetween('ends_at', [$since, $now]);
            })
            ->pluck('site_id')
            ->map(fn ($siteId) => $siteId ?: null)
            ->unique()
            ->values()
            ->all();

        $flushed = [];

        foreach ($siteIds as $siteId) {
            if ($siteId === null) {
                foreach (Sites::getSites() as $site) {
                    CacheInvalidator::flushSite((string) $site['id']);
                    $flushed[] = (string) $site['id'];
                }

                continue;
            }

            CacheInvalidator::flushSite((string) $siteId);
            $flushed[] = (string) $siteId;
        }

        return array_values(array_unique($flushed));
    }

    /** @param  mixed  $values  taalcode => waarde */
    public static function translated(mixed $values, ?string $locale = null): string
    {
        if (! is_array($values)) {
            return trim((string) $values);
        }

        $locale ??= app()->getLocale();
        $value = trim((string) ($values[$locale] ?? ''));

        if ($value !== '') {
            return $value;
        }

        foreach ($values as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return '';
    }

    /** Alleen een hexkleur komt in de stijl; al het andere wordt de standaard. */
    public static function color(?string $value, string $default): string
    {
        $value = trim((string) $value);

        return preg_match('/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value) === 1 ? $value : $default;
    }

    /** Alleen een pad op de eigen site of een http(s)-adres is een geldige link. */
    public static function safeUrl(string $url): ?string
    {
        $url = trim($url);

        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return $url;
        }

        return preg_match('#^https?://[^\s]+$#i', $url) === 1 ? $url : null;
    }

    protected static function tableExists(): bool
    {
        return (bool) rescue(fn () => Schema::hasTable('dashed__announcement_bars'), false, false);
    }
}
