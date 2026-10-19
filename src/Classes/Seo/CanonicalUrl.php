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
        $base = $model && method_exists($model, 'getUrl')
            ? (string) $model->getUrl(app()->getLocale(), false)
            : $request->url();

        if (parse_url($base, PHP_URL_PATH) !== '/') {
            $base = rtrim($base, '/');
        }

        $page = $request->query('page');

        if (is_numeric($page) && (int) $page >= 2) {
            return $base . '?page=' . (int) $page;
        }

        return $base;
    }
}
