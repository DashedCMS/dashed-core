<?php

namespace Dashed\DashedCore\Classes\Seo;

use Illuminate\Http\Request;

/**
 * Eén plek voor de canonical. Basis is de URL van het model als dat er is,
 * anders de verzoek-URL zonder query. Alleen ?page=N (N >= 2) blijft staan:
 * een gepagineerd overzicht verwijst naar zichzelf, een gefilterd of
 * doorzocht overzicht naar de kale pagina.
 */
class CanonicalUrl
{
    public static function for(Request $request, ?object $model): string
    {
        $base = $request->url();

        // De URL van het model alleen als hij dezelfde pagina is als het verzoek
        // (hooguit anders in hoofdletters of trailing slash). Een categorie met
        // één product geeft bijvoorbeeld de product-URL terug, en zonder
        // overzichtspagina een kale plaatshouder; dat is nooit de canonical.
        if ($model && method_exists($model, 'getUrl')) {
            $modelUrl = (string) $model->getUrl(app()->getLocale(), false);
            if (self::samePage($modelUrl, $base)) {
                $base = $modelUrl;
            }
        }

        // De homepage eindigt op een slash (zoals het WebSite-schema), elke
        // andere pagina niet (FrontendMiddleware stuurt een trailing slash weg).
        $path = (string) parse_url($base, PHP_URL_PATH);
        $base = $path === '' || $path === '/' ? rtrim($base, '/') . '/' : rtrim($base, '/');

        $page = $request->query('page');

        if (is_numeric($page) && (int) $page >= 2) {
            return $base . '?page=' . (int) $page;
        }

        return $base;
    }

    protected static function samePage(string $a, string $b): bool
    {
        // Alleen het pad: de host van url() kan van APP_URL komen waar het
        // verzoek via een andere (site-)host binnenkwam.
        $normalize = fn (string $url) => strtolower(trim((string) parse_url($url, PHP_URL_PATH), '/'));

        return str_starts_with($a, 'http') && $normalize($a) === $normalize($b);
    }
}
