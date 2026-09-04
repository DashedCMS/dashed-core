<?php

namespace Dashed\DashedCore\Classes;

/**
 * Vertaalt verouderde Ignition-middleware in config/flare.php naar Flare.
 *
 * Projecten hebben een config/flare.php die ooit door spatie/laravel-ignition
 * is gepubliceerd en de middleware aanwijst onder
 * Spatie\LaravelIgnition\FlareMiddleware\*. Ignition staat in require-dev, dus
 * op productie (composer install --no-dev) bestaan die klassen niet en valt
 * Flare om zodra hij zijn middleware instantieert:
 * Class "Spatie\LaravelIgnition\FlareMiddleware\AddNotifierName" not found.
 *
 * spatie/laravel-flare (harde afhankelijkheid van dashed-core) levert dezelfde
 * middleware onder Spatie\LaravelFlare\FlareMiddleware\*. Daar wijzen we naar
 * toe, zodat een verouderd config-bestand niet per project hoeft te worden
 * herschreven. Dit gebeurt in de register-fase; Flare bouwt zijn middleware
 * pas als de Flare-singleton voor het eerst wordt opgevraagd.
 */
class FlareMiddlewareCompat
{
    private const LEGACY_NAMESPACE = 'Spatie\\LaravelIgnition\\FlareMiddleware\\';

    private const CURRENT_NAMESPACE = 'Spatie\\LaravelFlare\\FlareMiddleware\\';

    public static function apply(): void
    {
        $middleware = config('flare.flare_middleware');

        if (! is_array($middleware)) {
            return;
        }

        $translated = [];

        foreach ($middleware as $key => $value) {
            if (is_string($key)) {
                $translated[self::translate($key)] = $value;
            } else {
                $translated[] = is_string($value) ? self::translate($value) : $value;
            }
        }

        config()->set('flare.flare_middleware', $translated);
    }

    public static function translate(string $class): string
    {
        if (! str_starts_with($class, self::LEGACY_NAMESPACE)) {
            return $class;
        }

        $replacement = self::CURRENT_NAMESPACE.substr($class, strlen(self::LEGACY_NAMESPACE));

        return class_exists($replacement) ? $replacement : $class;
    }
}
