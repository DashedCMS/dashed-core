<?php

namespace Dashed\DashedCore\Models;

use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\Classes\Locales;
use Illuminate\Database\Eloquent\SoftDeletes;
use Dashed\DashedCore\Classes\Redirects\RedirectChain;

class Redirect extends Model
{
    use SoftDeletes;

    protected $table = 'dashed__redirects';

    protected $casts = [
        'delete_redirect_after' => 'date',
    ];

    protected static function booted(): void
    {
        // Een redirect die naar zichzelf wijst of via een keten terugkomt wordt niet opgeslagen; een doel dat zelf
        // doorverwijst wordt meteen het eindadres (Lovora, 16-11-2026: 44 lussen in Search Console).
        static::saving(function (Redirect $redirect) {
            if ($redirect->isGone()) {
                $redirect->to = (string) $redirect->to;

                return null;
            }

            return $redirect->guardAgainstLoops();
        });

        static::saved(fn (Redirect $redirect) => $redirect->pointEarlierRedirectsHere());
    }

    public function isGone(): bool
    {
        return (int) $this->sort === RedirectChain::GONE;
    }

    /**
     * Het eindadres voor een verzoek op dit pad, in één stap: ['to' => ..., 'status' => 301|302|410], of null
     * als er geen redirect is of als die naar zichzelf terug zou leiden.
     *
     * @return array{to: string|null, status: int}|null
     */
    public static function resolveFor(string $path, ?int $exceptId = null, bool $requested = true): ?array
    {
        // Alleen het opgevraagde adres mag via het pad zonder taalprefix matchen. Een tussenstap is een adres dat
        // een redirect zelf noemt: /en/producten/x kan een live EN-pagina zijn, en de NL-redirect /producten/x
        // erachter stuurde EN-bezoekers dan naar een NL-pagina.
        $hop = $requested ? 0 : 1;

        return RedirectChain::resolve(
            $path,
            function (string $current) use ($exceptId, &$hop) {
                return self::lookup($current, $exceptId, $hop++ === 0);
            },
            parse_url((string) url('/'), PHP_URL_HOST) ?: null,
        );
    }

    /**
     * Eén stap: eerst het pad precies zoals gevraagd, pas daarna het pad zonder taalprefix. Vroeger telden beide
     * even zwaar, waardoor /de/producten/x de NL-redirect /producten/x -> /de/producten/x vond.
     *
     * @return array{to: string|null, status: int}|null
     */
    public static function lookup(string $path, ?int $exceptId = null, bool $withoutLocaleFallback = true): ?array
    {
        $redirect = self::findForSlugs([$path], $exceptId);

        if (! $redirect && $withoutLocaleFallback && ($withoutLocale = RedirectChain::withoutLocalePrefix($path, self::localeIds())) !== null) {
            $redirect = self::findForSlugs([$withoutLocale], $exceptId);
        }

        return $redirect ? ['to' => $redirect->to, 'status' => (int) $redirect->sort] : null;
    }

    /**
     * @return array<int, string>
     */
    public static function localeIds(): array
    {
        return array_values(array_filter(array_map(fn ($locale) => $locale['id'] ?? null, rescue(fn () => Locales::getLocales(), [], false))));
    }

    /**
     * Markeert een adres als definitief weg (410), tenzij er al een redirect vanaf staat.
     */
    public static function markGone(string $from): ?self
    {
        $from = RedirectChain::normalize($from);

        if ($from === '/' || self::findForSlugs([$from])) {
            return null;
        }

        $redirect = new self();
        $redirect->from = $from;
        $redirect->to = '';
        $redirect->sort = RedirectChain::GONE;
        $redirect->save();

        return $redirect;
    }

    /**
     * @return false|null  false houdt het opslaan tegen
     */
    public function guardAgainstLoops(): ?bool
    {
        $host = parse_url((string) url('/'), PHP_URL_HOST) ?: null;

        if (! RedirectChain::isInternal($this->to, $host)) {
            return null;
        }

        if (RedirectChain::same($this->from, $this->to)) {
            return false;
        }

        $final = self::resolveFor($this->to, $this->exists ? (int) $this->getKey() : null, false);

        if ($final === null || $final['to'] === null) {
            return null;
        }

        if (RedirectChain::isInternal($final['to'], $host) && RedirectChain::same($final['to'], $this->from)) {
            return false;
        }

        $this->to = $final['to'];

        return null;
    }

    /**
     * Redirects die naar het vertrekadres van deze wezen gaan meteen naar het eindadres, zodat er geen keten
     * ontstaat. Een redirect die daardoor naar zichzelf zou wijzen verdwijnt.
     */
    public function pointEarlierRedirectsHere(): void
    {
        if ($this->isGone() || ! RedirectChain::isInternal($this->to, parse_url((string) url('/'), PHP_URL_HOST) ?: null)) {
            return;
        }

        foreach (self::query()->whereIn('to', self::candidatesFor([$this->from]))->whereKeyNot($this->getKey())->get() as $earlier) {
            if (RedirectChain::same($earlier->from, $this->to)) {
                $earlier->deleteQuietly();

                continue;
            }

            $earlier->to = $this->to;
            $earlier->saveQuietly();
        }
    }

    /**
     * Alle vormen waarin een pad in `from` kan staan, met en zonder slashes.
     *
     * @param  array<int, string|null>  $slugs
     * @return list<string>
     */
    public static function candidatesFor(array $slugs): array
    {
        $candidates = [];

        foreach ($slugs as $slug) {
            $slug = trim($slug ?? '', '/');

            if ($slug === '') {
                continue;
            }

            $candidates[] = $slug;
            $candidates[] = '/' . $slug;
            $candidates[] = $slug . '/';
            $candidates[] = '/' . $slug . '/';
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @param  array<int, string|null>  $slugs
     */
    public static function findForSlugs(array $slugs, ?int $exceptId = null): ?self
    {
        $candidates = self::candidatesFor($slugs);

        if ($candidates === []) {
            return null;
        }

        return self::query()
            ->whereIn('from', $candidates)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->orderBy('id')
            ->first();
    }

    public function pointsToItself(): bool
    {
        return url($this->from) == url($this->to);
    }

    public static function handleSlugChangeForFilamentModel($record, string $locale, string $newSlug)
    {
        app()->setLocale($locale);
        $oldUrl = str($record->getUrl())->replace(url('/'), '')->toString();
        $newUrl = str($oldUrl)->replace($record->getTranslation('slug', $locale), $newSlug)->toString();
        self::handleSlugChange($oldUrl, $newUrl);
    }

    public static function handleSlugChange(?string $oldSlug, ?string $newSlug)
    {
        if ($oldSlug === $newSlug) {
            return;
        }

        // Een oud adres in een andere taal is geen slugwijziging. Zo ontstond /producten/x -> /de/producten/x toen
        // de Duitse URL zijn taalprefix kreeg: de NL-pagina verdween achter een redirect naar het Duits.
        $locales = self::localeIds();
        if (RedirectChain::localePrefix($oldSlug, $locales) !== RedirectChain::localePrefix($newSlug, $locales)) {
            return;
        }

        if (! str($newSlug)->startsWith('/')) {
            $newSlug = '/' . $newSlug;
        }

        Redirect::where('to', $oldSlug)->update([
            'to' => $newSlug,
        ]);

        if (! Redirect::where('from', $oldSlug)->where('to', $newSlug)->exists()) {
            $redirect = new Redirect();
            $redirect->from = $oldSlug;
            $redirect->to = $newSlug;
            $redirect->sort = '301';
            // Geen vervaldatum: na drie maanden gaf het oude adres weer een 404, terwijl Google en oude links het
            // nog jaren gebruiken.
            $redirect->save();
        }

        // Alleen rijen met `to` = de nieuwe slug zijn hierboven geraakt, dus alleen
        // die kunnen nu naar zichzelf wijzen. Oude lussen van elders ruimt
        // CleanupExpiredRedirects dagelijks op.
        foreach (Redirect::where('to', $newSlug)->get() as $redirect) {
            if ($redirect->pointsToItself()) {
                $redirect->delete();
            }
        }

        $routeModels = cms()->builder('routeModels');

        foreach ($routeModels as $routeModel) {
            if (method_exists($routeModel['class'], 'replaceInContent')) {
                $routeModel['class']::replaceInContent([
                    url($oldSlug) => url($newSlug),
                    'href="' . url($oldSlug) . '"' => 'href="' . url($newSlug) . '"',
                    'href="' . $oldSlug . '"' => 'href="' . $newSlug . '"',
                    'href="/' . $oldSlug . '"' => 'href="' . $newSlug . '"',
                ]);
            } elseif (isset($routeModel['routeHandler']) && method_exists($routeModel['routeHandler'], 'replaceInContent')) {
                $routeModel['routeHandler']::replaceInContent([
                    url($oldSlug) => url($newSlug),
                    'href="' . url($oldSlug) . '"' => 'href="' . url($newSlug) . '"',
                    'href="' . $oldSlug . '"' => 'href="' . $newSlug . '"',
                    'href="/' . $oldSlug . '"' => 'href="' . $newSlug . '"',
                ]);
            }
        }
    }
}
