<?php

namespace Dashed\DashedCore\Controllers\Frontend;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Dashed\DashedCore\Models\AbTest;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Classes\AbTests\AbTests;
use Dashed\DashedCore\Classes\Consent\ConsentGate;

/**
 * Meet een vertoning of klik van een A/B-test. Net als de toestemmingslog
 * van dashed-cookies buiten de web-groep: een pagina uit de responscache
 * draagt een oud CSRF-token. Het ergste wat een vervalst verzoek doet is een
 * teller ophogen; de throttle begrenst dat. Antwoordt altijd 204, zodat een
 * scanner niets leert over welke tests er lopen.
 */
class AbTestEventController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $test = (int) $request->input('test');
        $variant = (string) $request->input('variant');
        $event = (string) $request->input('event');

        if ($test < 1 || ! in_array($variant, ['a', 'b'], true) || ! in_array($event, AbTests::EVENTS, true)) {
            return response()->noContent();
        }

        $siteId = Sites::getActive();
        if (! AbTests::enabled($siteId) || ! ConsentGate::allows('analytics')) {
            return response()->noContent();
        }

        $abTest = AbTest::query()
            ->whereKey($test)
            ->where('site_id', $siteId)
            ->where('status', AbTest::STATUS_RUNNING)
            ->first();

        if ($abTest) {
            AbTests::increment($abTest, $variant, $event === 'view' ? 'views' : 'clicks');
        }

        return response()->noContent();
    }
}
