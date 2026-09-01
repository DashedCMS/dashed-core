<?php

namespace Dashed\DashedCore\Middleware;

use Illuminate\Http\Request;
use Dashed\DashedCore\Classes\Sites;
use Illuminate\Http\Middleware\TrustHosts;
use Dashed\DashedCore\Models\Customsetting;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;

/**
 * Laat een verzoek alleen door als de Host-header een van de eigen domeinen
 * is: de host van APP_URL, de site_url van elke site, en wat er in
 * dashed-core.trusted_hosts.extra staat. Exact, dus geen subdomeinen; Laravels
 * eigen standaard (alle subdomeinen van APP_URL) zou evil.shop.example
 * gewoon toelaten.
 *
 * Waarom dit bestaat: url(), route() en URL::signedRoute() bouwen de absolute
 * URL uit de Host-header van het verzoek. Zonder deze controle bepaalt wie
 * een wachtwoord-reset aanvraagt dus het domein in de mail die de gebruiker
 * krijgt. Token en handtekening landen dan op zijn server, en omdat dezelfde
 * vervalste Host ook bij het inwisselen wordt geaccepteerd (de handtekening
 * dekt schema en host van het verzoek), kan hij daarmee het wachtwoord zetten.
 *
 * Staat vooraan in de globale middleware, vóór alles wat een URL uit het
 * verzoek afleidt. Net als Laravel zelf niet afgedwongen in local en in
 * tests, tenzij dashed-core.trusted_hosts.enforce dat expliciet zegt.
 */
class TrustedHosts extends TrustHosts
{
    public function handle(Request $request, $next)
    {
        if (! $this->shouldSpecifyTrustedHosts()) {
            return $next($request);
        }

        $patterns = array_values(array_filter($this->hosts()));

        // Zonder enige bekende host valt er niets te vergelijken; dan liever
        // doorlaten dan elke bezoeker buitensluiten.
        if ($patterns === []) {
            return $next($request);
        }

        Request::setTrustedHosts($patterns);

        try {
            $request->getHost();
        } catch (SuspiciousOperationException) {
            // Laravel 12 zet deze Symfony-uitzondering nergens om; zonder deze
            // vangst zou een vreemde Host een 500 geven in plaats van een 400.
            abort(400, 'Bad hostname provided.');
        }

        return $next($request);
    }

    public function hosts(): array
    {
        return array_map(
            fn (string $host) => '^' . preg_quote($host) . '$',
            static::allowedHosts(),
        );
    }

    /**
     * De hosts die dit systeem mogen aanspreken, in kleine letters, zonder
     * poort en zonder dubbelen.
     *
     * @return list<string>
     */
    public static function allowedHosts(): array
    {
        $values = [config('app.url')];

        foreach (Sites::getSites() as $site) {
            // Vóór de eerste migratie bestaat de instellingentabel nog niet.
            $values[] = rescue(fn () => Customsetting::get('site_url', $site['id']), null, false);
        }

        foreach ((array) config('dashed-core.trusted_hosts.extra', []) as $extra) {
            $values[] = $extra;
        }

        $hosts = array_map(fn ($value) => static::hostOf((string) $value), $values);

        return array_values(array_unique(array_filter($hosts)));
    }

    protected static function hostOf(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        // Een kale host ("shop.example") heeft geen schema; parse_url ziet die
        // dan als pad in plaats van als host.
        $host = parse_url(str_contains($value, '://') ? $value : 'https://' . $value, PHP_URL_HOST);

        return $host ? strtolower($host) : null;
    }

    protected function shouldSpecifyTrustedHosts()
    {
        $enforce = config('dashed-core.trusted_hosts.enforce');

        if ($enforce !== null && $enforce !== '') {
            return filter_var($enforce, FILTER_VALIDATE_BOOL);
        }

        return parent::shouldSpecifyTrustedHosts();
    }
}
