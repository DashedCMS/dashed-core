<?php

namespace Dashed\DashedCore\Classes;

/**
 * De inhoud van /robots.txt, opgebouwd uit wat de pakketten aanmelden.
 *
 * Elk pakket meldt zijn eigen paden aan met cms()->builder('robotsDisallow',
 * [...]): dashed-core het CMS-pad, Horizon, Livewire en de opslag van
 * facturen; dashed-ecommerce-core zijn bestel-, winkelwagen- en
 * downloadroutes. Zo hoeft geen pakket van een ander te weten wat er
 * geheim moet blijven. Een fysiek public/robots.txt gaat op de webserver
 * vóór deze route; dat bestand hoort dus weg zodra deze route er is.
 *
 * Lokaal is de hele site uitgesloten, in lijn met de noindex-metatag die
 * FrontendMiddleware daar zet.
 */
class RobotsTxtBuilder
{
    public const BUILDER = 'robotsDisallow';

    public static function build(): string
    {
        $lines = ['User-agent: *'];

        if (app()->isLocal()) {
            $lines[] = 'Disallow: /';

            return implode("\n", $lines) . "\n";
        }

        foreach (self::disallowedPaths() as $path) {
            $lines[] = 'Disallow: ' . $path;
        }

        $lines[] = '';
        $lines[] = 'Sitemap: ' . rtrim((string) config('app.url'), '/') . '/sitemap.xml';

        return implode("\n", $lines) . "\n";
    }

    /**
     * Aangemelde paden, genormaliseerd (leidende slash, geen dubbelen), in
     * volgorde van aanmelding.
     *
     * @return array<int, string>
     */
    public static function disallowedPaths(): array
    {
        $paths = [];

        foreach ((array) cms()->builder(self::BUILDER) as $path) {
            $path = trim((string) $path);

            if ($path === '') {
                continue;
            }

            $path = '/' . ltrim($path, '/');

            if (! in_array($path, $paths, true)) {
                $paths[] = $path;
            }
        }

        return $paths;
    }
}
