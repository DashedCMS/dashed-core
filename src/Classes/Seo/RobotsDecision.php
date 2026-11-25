<?php

namespace Dashed\DashedCore\Classes\Seo;

use Illuminate\Http\Request;
use Dashed\DashedCore\Models\Customsetting;

/**
 * Eén plek voor de robots-regel. Lokaal blijft alles buiten de index (ook
 * nofollow, zoals het was). Een zoek-, filter- of sorteerparameter, het
 * vinkje op het model, of een pagina die via de haak noindexPageSettings als
 * winkelwagen, afrekenen, zoeken, login of account geregistreerd staat,
 * geeft noindex met follow: de links op zo'n pagina mogen gewoon gevolgd
 * worden. ?page=N alleen is geen reden.
 */
class RobotsDecision
{
    public const QUERY_KEYS = ['search', 'activeFilters', 'sortBy', 'sort-by', 'order', 'pagination'];

    /**
     * Lokaal en de omgevingen uit `dashed-core.noindex_environments` (staging, acceptatie, ...) mogen nooit in Google.
     * Bewust een lijst met namen: een onbekende APP_ENV op een live site blijft indexeerbaar.
     */
    public static function isBlockedEnvironment(): bool
    {
        return app()->isLocal() || app()->environment((array) config('dashed-core.noindex_environments', []));
    }

    public const NOT_FOUND_ATTRIBUTE = 'dashed-core.not-found';

    /** Markeert het verzoek als 404/410, zodat de head noindex en geen canonical krijgt. */
    public static function markNotFound(?Request $request = null): void
    {
        ($request ?? request())->attributes->set(self::NOT_FOUND_ATTRIBUTE, true);
    }

    public static function isNotFound(?Request $request = null): bool
    {
        return (bool) ($request ?? request())->attributes->get(self::NOT_FOUND_ATTRIBUTE, false);
    }

    public static function for(Request $request, ?object $model, string $siteId): string
    {
        if (self::isBlockedEnvironment()) {
            return 'noindex, nofollow';
        }

        // Een 404/410-pagina: de head zegt hetzelfde als de statuscode.
        if (self::isNotFound($request)) {
            return 'noindex, follow';
        }

        if ($model && self::modelWantsNoindex($model)) {
            return 'noindex, follow';
        }

        if (self::hasFilterQuery($request)) {
            return 'noindex, follow';
        }

        if ($model && self::isRegisteredNoindexPage($model, $siteId)) {
            return 'noindex, follow';
        }

        if (self::isRegisteredNoindexPath($request)) {
            return 'noindex, follow';
        }

        return 'index, follow';
    }

    /**
     * Alleen de query-string zoals de bezoeker hem stuurde telt. Een
     * Livewire-component met #[Url]-eigenschappen (het productoverzicht)
     * schrijft bij het renderen zijn standaardwaarden in $request->query,
     * waardoor elke overzichtspagina zonder parameters noindex kreeg. Een
     * lege waarde (?search=) is geen filter.
     */
    protected static function hasFilterQuery(Request $request): bool
    {
        parse_str((string) $request->server->get('QUERY_STRING', ''), $query);

        foreach (self::QUERY_KEYS as $key) {
            $value = $query[$key] ?? null;

            if ($value !== null && $value !== '' && $value !== []) {
                return true;
            }
        }

        return false;
    }

    protected static function modelWantsNoindex(object $model): bool
    {
        try {
            $metadata = $model->metadata ?? null;
        } catch (\Throwable) {
            return false;
        }

        return is_object($metadata) && (bool) ($metadata->noindex ?? false);
    }

    /**
     * Routes zonder paginamodel (retourstatus, proforma, betaallink) melden
     * zich met een padprefix via de haak noindexPaths; een taalprefix
     * ervoor telt niet mee.
     */
    protected static function isRegisteredNoindexPath(Request $request): bool
    {
        $segments = array_values(array_filter(explode('/', trim($request->path(), '/'))));

        if ($segments !== [] && array_key_exists($segments[0], (array) config('laravellocalization.supportedLocales', []))) {
            array_shift($segments);
        }

        $path = implode('/', $segments);

        foreach (cms()->builder('noindexPaths') as $prefix) {
            $prefix = trim((string) $prefix, '/');

            if ($prefix !== '' && ($path === $prefix || str_starts_with($path, $prefix . '/'))) {
                return true;
            }
        }

        return false;
    }

    public static function isRegisteredNoindexPage(object $model, string $siteId): bool
    {
        if (! class_exists(\Dashed\DashedPages\Models\Page::class) || ! $model instanceof \Dashed\DashedPages\Models\Page) {
            return false;
        }

        foreach (cms()->builder('noindexPageSettings') as $setting) {
            $pageId = Customsetting::get($setting, $siteId);

            if ($pageId !== null && $pageId !== '' && (string) $pageId === (string) $model->getKey()) {
                return true;
            }
        }

        return false;
    }
}
