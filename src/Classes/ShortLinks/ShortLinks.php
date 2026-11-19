<?php

namespace Dashed\DashedCore\Classes\ShortLinks;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Dashed\DashedCore\Classes\Sites;
use Illuminate\Support\Facades\Schema;
use Dashed\DashedCore\Models\ShortLink;
use Illuminate\Database\Eloquent\Builder;
use Dashed\DashedCore\Middleware\TrustedHosts;

/**
 * De regels achter de UTM-linkbouwer en de korte links /k/{code}.
 *
 * Een korte link stuurt alleen door naar de eigen site (de hosts uit
 * TrustedHosts::allowedHosts()) of naar een host die in de config
 * dashed-core.short_links.allowed_hosts staat. Dat wordt bij opslaan
 * getoetst en bij elke klik opnieuw, zodat een link die via de database of
 * een gewijzigde site_url ergens anders heen wijst nooit een open redirect
 * wordt.
 *
 * Kliks worden per link per dag opgeteld, zonder IP of user agent, en bots
 * tellen niet mee.
 */
class ShortLinks
{
    public const PREFIX = 'k';

    public const UTM_FIELDS = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content'];

    /** Zonder 0/O, 1/l/I: een code wordt ook overgetypt van een poster. */
    protected const ALPHABET = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public const CODE_LENGTH = 6;

    public const CODE_PATTERN = '[A-Za-z0-9_-]{3,32}';

    /** Statussen waarin een bestelling als omzet telt, zoals elders in de webshop. */
    public const PAID_STATUSES = ['paid', 'partially_paid', 'waiting_for_confirmation'];

    public const BOT_PATTERN = '/bot\b|bot[\/;) ]|crawl|spider|slurp|mediapartners|facebookexternalhit|facebookcatalog|meta-external|whatsapp|telegram|slackbot|discordbot|preview|lighthouse|pagespeed|gtmetrix|pingdom|uptime|monitor|headless|phantomjs|python|curl|wget|httpclient|okhttp|go-http|java\/|libwww|scrapy|axios|node-fetch|guzzle|postman|insomnia|ahrefs|semrush|screaming|bytespider|gptbot|claudebot|ccbot|chatgpt|perplexity|dataprovider|feedfetcher|validator|safelinks|proofpoint|mimecast|barracuda/i';

    /**
     * Voorinstellingen per kanaal: bron en medium zoals Google Analytics ze
     * in de standaardkanaalgroepen herkent.
     *
     * @return array<string, array{label: string, utm_source: string, utm_medium: string}>
     */
    public static function presets(): array
    {
        return [
            'facebook' => ['label' => 'Facebook', 'utm_source' => 'facebook', 'utm_medium' => 'social'],
            'instagram' => ['label' => 'Instagram', 'utm_source' => 'instagram', 'utm_medium' => 'social'],
            'linkedin' => ['label' => 'LinkedIn', 'utm_source' => 'linkedin', 'utm_medium' => 'social'],
            'tiktok' => ['label' => 'TikTok', 'utm_source' => 'tiktok', 'utm_medium' => 'social'],
            'pinterest' => ['label' => 'Pinterest', 'utm_source' => 'pinterest', 'utm_medium' => 'social'],
            'whatsapp' => ['label' => 'WhatsApp', 'utm_source' => 'whatsapp', 'utm_medium' => 'social'],
            'newsletter' => ['label' => __('Nieuwsbrief'), 'utm_source' => 'newsletter', 'utm_medium' => 'email'],
            'google_ads' => ['label' => 'Google Ads', 'utm_source' => 'google', 'utm_medium' => 'cpc'],
            'meta_ads' => ['label' => 'Meta Ads', 'utm_source' => 'facebook', 'utm_medium' => 'paid_social'],
            'print' => ['label' => __('Drukwerk en QR-code'), 'utm_source' => 'print', 'utm_medium' => 'qr'],
            'partner' => ['label' => __('Partner of affiliate'), 'utm_source' => 'partner', 'utm_medium' => 'referral'],
        ];
    }

    public static function newCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (ShortLink::query()->where('code', $code)->exists());

        return $code;
    }

    /** Een pad als /actie wordt een adres op het domein van de site. */
    public static function absolute(string $url, $siteId = null): string
    {
        $url = trim($url);

        if (str_starts_with($url, '/') && ! str_starts_with($url, '//')) {
            return Sites::url($url, $siteId);
        }

        return $url;
    }

    /**
     * Mag een korte link hierheen doorsturen? Alleen http(s), zonder
     * inloggegevens in de URL, naar de eigen site of een toegestane host.
     */
    public static function isAllowedDestination(string $url, $siteId = null): bool
    {
        $url = self::absolute($url, $siteId);

        if ($url === '' || preg_match('/[\s\\\\\x00-\x1F\x7F]/', $url) === 1) {
            return false;
        }

        $parts = parse_url($url);

        if ($parts === false || ! in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https'], true)) {
            return false;
        }

        if (isset($parts['user']) || isset($parts['pass']) || empty($parts['host'])) {
            return false;
        }

        return in_array(strtolower($parts['host']), self::allowedHosts(), true);
    }

    /** @return list<string> */
    public static function allowedHosts(): array
    {
        $hosts = TrustedHosts::allowedHosts();

        foreach ((array) config('dashed-core.short_links.allowed_hosts', []) as $host) {
            $host = strtolower(trim((string) $host));
            if ($host !== '') {
                $hosts[] = $host;
            }
        }

        return array_values(array_unique($hosts));
    }

    /**
     * Voegt de UTM-velden toe aan de doel-URL. Bestaande queryparameters
     * blijven staan, een UTM-veld van de link gaat voor een gelijknamige
     * parameter in het doel, en een #anker blijft achteraan.
     *
     * @param  array<string, string>  $utm
     */
    public static function buildTargetUrl(string $url, array $utm): string
    {
        $fragment = '';
        if (($hash = strpos($url, '#')) !== false) {
            $fragment = substr($url, $hash);
            $url = substr($url, 0, $hash);
        }

        $query = [];
        if (($question = strpos($url, '?')) !== false) {
            parse_str(substr($url, $question + 1), $query);
            $url = substr($url, 0, $question);
        }

        $query = array_merge($query, array_filter($utm, fn ($value) => $value !== ''));

        return $url . ($query ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986) : '') . $fragment;
    }

    /** Bots, linkscanners en prefetches tellen niet als klik. */
    public static function isCountable(Request $request): bool
    {
        if (! $request->isMethod('GET')) {
            return false;
        }

        $purpose = strtolower((string) ($request->header('Sec-Purpose') ?: $request->header('Purpose') ?: $request->header('X-Moz')));
        if (str_contains($purpose, 'prefetch') || str_contains($purpose, 'prerender')) {
            return false;
        }

        $userAgent = trim((string) $request->userAgent());

        return $userAgent !== '' && preg_match(self::BOT_PATTERN, $userAgent) !== 1;
    }

    /** Telt één klik op bij de link en bij zijn dagtotaal. */
    public static function registerClick(ShortLink $link): void
    {
        $now = Carbon::now();

        DB::table('dashed__short_link_clicks')->upsert(
            [['short_link_id' => $link->id, 'date' => $now->toDateString(), 'clicks' => 1]],
            ['short_link_id', 'date'],
            ['clicks' => DB::raw('clicks + 1')],
        );

        ShortLink::query()->whereKey($link->id)->update([
            'click_count' => DB::raw('click_count + 1'),
            'last_clicked_at' => $now,
        ]);
    }

    /**
     * Zet kliks van de laatste dertig dagen, het aantal bestellingen en de
     * omzet als kolommen op de query: correlated subqueries, dus één query
     * voor de hele lijst.
     *
     * Een bestelling hoort bij een link als bron en campagne gelijk zijn, en
     * medium en inhoud ook zodra de link die heeft. De bestelling bewaart de
     * UTM-velden van de laatste touch (AttributionTracker in de webshop).
     */
    public static function withStats(Builder $query): Builder
    {
        $links = (new ShortLink())->getTable();

        $query->select($links . '.*')->selectSub(
            DB::table('dashed__short_link_clicks')
                ->selectRaw('COALESCE(SUM(clicks), 0)')
                ->whereColumn('dashed__short_link_clicks.short_link_id', $links . '.id')
                ->where('date', '>=', Carbon::now()->subDays(29)->toDateString()),
            'clicks_30_days'
        );

        if (! self::ordersHaveAttribution()) {
            return $query;
        }

        $orders = fn (string $select) => DB::table('dashed__orders')
            ->selectRaw($select)
            ->whereNull('dashed__orders.deleted_at')
            ->whereIn('dashed__orders.status', self::PAID_STATUSES)
            ->whereNotNull($links . '.utm_campaign')
            ->whereColumn('dashed__orders.utm_campaign', $links . '.utm_campaign')
            ->whereColumn('dashed__orders.utm_source', $links . '.utm_source')
            ->where(fn ($q) => $q->whereNull($links . '.utm_medium')->orWhereColumn('dashed__orders.utm_medium', $links . '.utm_medium'))
            ->where(fn ($q) => $q->whereNull($links . '.utm_content')->orWhereColumn('dashed__orders.utm_content', $links . '.utm_content'));

        return $query
            ->selectSub($orders('COUNT(*)'), 'orders_count')
            ->selectSub($orders('COALESCE(SUM(dashed__orders.total), 0)'), 'orders_revenue');
    }

    public static function ordersHaveAttribution(): bool
    {
        return (bool) rescue(
            fn () => Schema::hasTable('dashed__orders') && Schema::hasColumn('dashed__orders', 'utm_campaign') && Schema::hasColumn('dashed__orders', 'utm_content'),
            false,
            false,
        );
    }
}
