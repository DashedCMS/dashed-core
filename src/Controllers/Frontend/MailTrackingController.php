<?php

namespace Dashed\DashedCore\Controllers\Frontend;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use Dashed\DashedCore\Models\SentEmail;
use Dashed\DashedCore\Classes\MailTracking;

/**
 * Kliks en opens uit uitgaande mails. Zonder sessie en cookies, en nooit uit
 * een cache. Een meting die mislukt mag de bezoeker niet tegenhouden.
 */
class MailTrackingController
{
    /** Een transparante gif van 1 bij 1 pixel. */
    protected const PIXEL = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    protected const NO_CACHE = 'no-store, private';

    public function click(Request $request, string $id): RedirectResponse
    {
        $url = MailTracking::destination($id, (string) $request->query('u'), (string) $request->query('s'));

        if ($url === null || preg_match('#^https?://#i', $url) !== 1) {
            return redirect('/')->header('Cache-Control', self::NO_CACHE);
        }

        if ($request->isMethod('GET')) {
            $this->count($id, 'clicked_at', 'click_count', ['last_clicked_url' => $url]);
        }

        return redirect()->away($url)->header('Cache-Control', self::NO_CACHE);
    }

    public function open(Request $request, string $id): Response
    {
        if ($request->isMethod('GET')) {
            $this->count($id, 'opened_at', 'open_count');
        }

        return response(base64_decode(self::PIXEL))
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', self::NO_CACHE);
    }

    /** @param  array<string, mixed>  $extra */
    protected function count(string $id, string $firstAt, string $counter, array $extra = []): void
    {
        try {
            SentEmail::query()->where('tracking_id', $id)->update($extra + [
                $firstAt => DB::raw('COALESCE(' . $firstAt . ', ' . DB::getPdo()->quote(Carbon::now()->toDateTimeString()) . ')'),
                $counter => DB::raw($counter . ' + 1'),
            ]);
        } catch (\Throwable $e) {
            Log::error('MailTrackingController kon de meting niet opslaan: ' . $e->getMessage());
        }
    }
}
