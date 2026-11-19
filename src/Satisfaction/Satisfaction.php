<?php

namespace Dashed\DashedCore\Satisfaction;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Models\SatisfactionRating;

/**
 * Klanttevredenheid: één vraag (score 1 tot 5, optioneel een opmerking) na
 * een chatgesprek en een paar dagen na levering. Dit is de generieke kant in
 * dashed-core: opslag, ondertekende links, de antwoordpagina en de
 * instellingen. Wanneer een vraag gesteld wordt beslissen de pakketten
 * (livechat in de transcriptmail, ec-core met een dagelijkse ronde).
 *
 * Alles staat standaard uit, per site en per bron.
 *
 * Een klik in de mail slaat nog niets op: de pagina toont de gekozen score
 * met een knop om te bevestigen. Mailbeveiligers openen links automatisch,
 * en een scanner die alle vijf de scores aanklikt mag geen score achterlaten.
 */
class Satisfaction
{
    public const SOURCE_CHAT = 'chat';

    public const SOURCE_DELIVERY = 'delivery';

    public const LOW_SCORE = 2;

    public const DEFAULT_DELIVERY_DAYS = 7;

    /** Zo lang blijven de links in een mail bruikbaar. */
    public const LINK_DAYS = 60;

    public const ROUTE_SHOW = 'dashed.frontend.satisfaction.show';


    /** @return array<string, string> */
    public static function sources(): array
    {
        return [
            self::SOURCE_CHAT => __('Chat'),
            self::SOURCE_DELIVERY => __('Levering'),
        ];
    }

    public static function enabled(string $source, ?string $siteId = null): bool
    {
        return (bool) Customsetting::get('satisfaction_'.$source.'_enabled', $siteId ?: Sites::getActive(), false);
    }

    public static function deliveryDays(?string $siteId = null): int
    {
        $days = (int) Customsetting::get('satisfaction_delivery_days', $siteId ?: Sites::getActive(), self::DEFAULT_DELIVERY_DAYS);

        return max(1, min(60, $days ?: self::DEFAULT_DELIVERY_DAYS));
    }

    /**
     * Legt een vraag vast en geeft de rij terug; de aanroeper zet de links
     * in zijn mail met scoreUrls(). Een tweede vraag over hetzelfde
     * onderwerp en dezelfde bron geeft de bestaande rij terug.
     *
     * @param  array{site_id?: ?string, order_id?: ?int, staff_user_id?: ?int, staff_name?: ?string, email?: ?string, locale?: ?string}  $attributes
     */
    public static function request(string $source, ?Model $subject, array $attributes = []): SatisfactionRating
    {
        if ($subject) {
            $existing = SatisfactionRating::query()
                ->where('source', $source)
                ->where('subject_type', $subject->getMorphClass())
                ->where('subject_id', $subject->getKey())
                ->first();

            if ($existing) {
                return $existing;
            }
        }

        return SatisfactionRating::create([
            'source' => $source,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'site_id' => $attributes['site_id'] ?? null,
            'order_id' => $attributes['order_id'] ?? null,
            'staff_user_id' => $attributes['staff_user_id'] ?? null,
            'staff_name' => isset($attributes['staff_name']) ? Str::limit((string) $attributes['staff_name'], 250, '') : null,
            'email' => $attributes['email'] ?? null,
            'locale' => $attributes['locale'] ?? app()->getLocale(),
            'token' => Str::random(40),
            'sent_at' => now(),
        ]);
    }

    /**
     * Vijf ondertekende links, score 1 tot 5. De root komt van de site van
     * de vraag en nooit van het verzoek, zie "Vertrouwde hosts".
     *
     * @return array<int, string>
     */
    public static function scoreUrls(SatisfactionRating $rating): array
    {
        return Sites::withForcedRootUrl(function () use ($rating) {
            $urls = [];
            foreach (range(1, 5) as $score) {
                $urls[$score] = URL::temporarySignedRoute(self::ROUTE_SHOW, now()->addDays(self::LINK_DAYS), [
                    'token' => $rating->token,
                    'score' => $score,
                ]);
            }

            return $urls;
        }, $rating->site_id);
    }

    /**
     * Klantteksten (mail en antwoordpagina) lopen via de front-end
     * vertalingen, zodat een site in een andere taal ze kan aanpassen.
     *
     * @param  array<string, string|int>  $variables
     */
    public static function text(string $key, string $default, array $variables = []): string
    {
        if (class_exists(\Dashed\DashedTranslations\Models\Translation::class)) {
            $value = rescue(fn () => \Dashed\DashedTranslations\Models\Translation::get('satisfaction-'.$key, 'satisfaction', $default, 'text', $variables), null, false);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        foreach ($variables as $name => $value) {
            $default = str_replace(':'.$name.':', (string) $value, $default);
        }

        return $default;
    }

    /** Het mailblok met de vraag en vijf klikbare scores, als HTML-rij voor dashed-core::emails.layout. */
    public static function mailBlock(SatisfactionRating $rating, string $question, ?string $primaryColor = null): string
    {
        return view('dashed-core::satisfaction.mail-block', [
            'question' => $question,
            'urls' => self::scoreUrls($rating),
            'primaryColor' => $primaryColor ?: (\Dashed\DashedCore\Mail\MailBranding::for($rating->site_id)['primaryColor'] ?? '#111827'),
            'low' => self::text('scale-low', 'Slecht'),
            'high' => self::text('scale-high', 'Uitstekend'),
        ])->render();
    }

    public static function record(SatisfactionRating $rating, int $score, ?string $comment = null): SatisfactionRating
    {
        $rating->forceFill([
            'score' => max(1, min(5, $score)),
            'comment' => filled($comment) ? Str::limit(trim(strip_tags((string) $comment)), 2000, '') : $rating->comment,
            'rated_at' => now(),
        ])->save();

        return $rating;
    }

    /**
     * Gemiddelde score per week en per bron over de laatste weken, als
     * aggregaat in de database.
     *
     * @return array{labels: array<int, string>, series: array<string, array<int, ?float>>}
     */
    public static function weeklyAverages(int $weeks = 12): array
    {
        $from = now()->subWeeks($weeks - 1)->startOfWeek();
        $period = \Dashed\DashedCore\Classes\Reports\SqlPeriod::expression('rated_at', 'week');

        $rows = SatisfactionRating::query()
            ->rated()
            ->where('rated_at', '>=', $from)
            ->toBase()
            ->selectRaw("{$period} as period, source, AVG(score) as average, COUNT(*) as total")
            ->groupByRaw("{$period}, source")
            ->get();

        $keys = $rows->pluck('period')->unique()->sort()->values()->all();
        $series = [];
        foreach (array_keys(self::sources()) as $source) {
            $series[$source] = array_map(function ($key) use ($rows, $source) {
                $row = $rows->first(fn ($row) => $row->period === $key && $row->source === $source);

                return $row ? round((float) $row->average, 2) : null;
            }, $keys);
        }

        return [
            'labels' => array_map(fn ($key) => \Dashed\DashedCore\Classes\Reports\SqlPeriod::label($key, 'week'), $keys),
            'series' => $series,
        ];
    }

    /**
     * @return array<string, array{average: ?float, total: int, sent: int}>
     */
    public static function summary(int $days = 30): array
    {
        $rows = SatisfactionRating::query()
            ->where('sent_at', '>=', now()->subDays($days))
            ->toBase()
            ->selectRaw('source, AVG(score) as average, COUNT(score) as total, COUNT(*) as sent')
            ->groupBy('source')
            ->get()
            ->keyBy('source');

        $result = [];
        foreach (array_keys(self::sources()) as $source) {
            $row = $rows->get($source);
            $result[$source] = [
                'average' => $row && $row->total ? round((float) $row->average, 2) : null,
                'total' => (int) ($row->total ?? 0),
                'sent' => (int) ($row->sent ?? 0),
            ];
        }

        return $result;
    }
}
