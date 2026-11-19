<?php

namespace Dashed\DashedCore\Controllers\Frontend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Dashed\DashedCore\Models\ShortLink;
use Dashed\DashedCore\Classes\ShortLinks\ShortLinks;

/**
 * /k/{code}: tijdelijke doorverwijzing (302) naar het doel van een korte
 * link, met de UTM-velden erbij. Buiten de web-groep en nooit uit een cache,
 * zodat elke klik de server bereikt en de doorverwijzing meteen een
 * gewijzigd doel volgt.
 */
class ShortLinkController
{
    protected const NO_CACHE = 'no-store, private';

    public function __invoke(Request $request, string $code): RedirectResponse
    {
        $link = ShortLink::query()->where('code', $code)->first();

        if (! $link) {
            abort(404);
        }

        // Ook bij elke klik: een doel dat na opslaan buiten de eigen site
        // terechtkwam (database, gewijzigde site_url) wordt nooit gevolgd.
        if (! ShortLinks::isAllowedDestination((string) $link->destination_url, $link->site_id)) {
            return redirect(\Dashed\DashedCore\Classes\Sites::url('/', $link->site_id), 302)->header('Cache-Control', self::NO_CACHE);
        }

        if (ShortLinks::isCountable($request)) {
            try {
                ShortLinks::registerClick($link);
            } catch (\Throwable $e) {
                Log::error('ShortLinkController kon de klik niet tellen: ' . $e->getMessage());
            }
        }

        return redirect()->away($link->targetUrl(), 302)
            ->header('Cache-Control', self::NO_CACHE)
            ->header('X-Robots-Tag', 'noindex');
    }
}
