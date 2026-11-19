<?php

namespace Dashed\DashedCore\Controllers\Frontend;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Dashed\DashedCore\Mail\MailBranding;
use Dashed\DashedCore\Satisfaction\Satisfaction;
use Dashed\DashedCore\Models\SatisfactionRating;

/**
 * De pagina achter de vijf scores in een tevredenheidsmail. GET toont de
 * gekozen score met een opmerkingveld en slaat nog niets op (mailscanners
 * openen links vanzelf), POST legt score en opmerking vast. De route is
 * ondertekend, dus token en score komen niet uit een geraden URL.
 */
class SatisfactionController extends Controller
{
    public function __invoke(Request $request, string $token, int $score)
    {
        $rating = SatisfactionRating::query()->where('token', $token)->firstOrFail();
        abort_unless($score >= 1 && $score <= 5, 404);

        if ($rating->locale) {
            app()->setLocale($rating->locale);
        }

        $saved = false;
        if ($request->isMethod('post')) {
            $data = $request->validate([
                'score' => ['required', 'integer', 'between:1,5'],
                'comment' => ['nullable', 'string', 'max:2000'],
            ]);
            Satisfaction::record($rating, (int) $data['score'], $data['comment'] ?? null);
            $saved = true;
            $score = (int) $data['score'];
        }

        return response()
            ->view('dashed-core::satisfaction.page', [
                'rating' => $rating,
                'score' => $score,
                'saved' => $saved,
                'branding' => MailBranding::for($rating->site_id),
                'action' => $request->fullUrl(),
            ])
            ->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Cache-Control', 'no-store, private');
    }
}
