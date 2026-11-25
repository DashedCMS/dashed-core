<?php

namespace Dashed\DashedCore\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Dashed\DashedCore\Classes\NavigationUsage;

/**
 * Het speldje naast een menu-item: pint vast of maakt los, in het kopje of
 * (met `top`) helemaal bovenaan. Alleen een pad dat
 * nu in het menu van deze gebruiker staat mag erin, anders kan iedereen hier
 * willekeurige tekst in de tabel zetten.
 */
class NavigationPinController
{
    public function __invoke(Request $request): JsonResponse
    {
        $key = NavigationUsage::keyFor((string) $request->input('url'));

        abort_unless($key !== null && in_array($key, NavigationUsage::itemKeys(), true), 422);

        return response()->json([
            'pinned' => NavigationUsage::togglePin($request->user(), $key, $request->boolean('top')),
        ]);
    }
}
