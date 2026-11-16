<?php

declare(strict_types=1);

use Dashed\DashedCore\Classes\Redirects\RedirectChain;

/**
 * Lovora (16-11-2026): /producten/x -> /de/producten/x matchte via het pad zonder taalprefix ook op
 * /de/producten/x, een 301 naar zichzelf. Google vond 44 van zulke lussen.
 */
function lookupFrom(array $redirects): callable
{
    return function (string $path) use ($redirects) {
        if (! array_key_exists($path, $redirects)) {
            return null;
        }

        $to = $redirects[$path];

        return $to === 410 ? ['to' => null, 'status' => 410] : ['to' => $to, 'status' => 301];
    };
}

it('returns nothing without a redirect', function () {
    expect(RedirectChain::resolve('/a', lookupFrom([])))->toBeNull();
});

it('follows a chain to the final address in one step', function () {
    $lookup = lookupFrom(['/a' => '/b', '/b' => '/c', '/c' => '/d']);

    expect(RedirectChain::resolve('/a', $lookup))->toBe(['to' => '/d', 'status' => 301]);
});

it('never redirects to itself', function () {
    expect(RedirectChain::resolve('/de/producten/x', lookupFrom(['/de/producten/x' => '/de/producten/x/'])))->toBeNull();
});

it('gives no redirect for a loop', function () {
    expect(RedirectChain::resolve('/a', lookupFrom(['/a' => '/b', '/b' => '/a'])))->toBeNull()
        ->and(RedirectChain::resolve('/a', lookupFrom(['/a' => '/b', '/b' => '/c', '/c' => '/b'])))->toBeNull();
});

it('stops at an external address', function () {
    $lookup = lookupFrom(['/a' => 'https://instagram.com/lovora', '/b' => 'https://lovora.nl/c', '/c' => '/d']);

    expect(RedirectChain::resolve('/a', $lookup, 'lovora.nl'))->toBe(['to' => 'https://instagram.com/lovora', 'status' => 301])
        ->and(RedirectChain::resolve('/b', $lookup, 'lovora.nl'))->toBe(['to' => '/d', 'status' => 301]);
});

it('ends in gone when the chain reaches a 410', function () {
    expect(RedirectChain::resolve('/a', lookupFrom(['/a' => '/b', '/b' => 410])))->toBe(['to' => null, 'status' => 410]);
});

it('normalizes urls to a path and keeps case', function () {
    expect(RedirectChain::normalize('https://lovora.nl/en/V/?x=1'))->toBe('/en/V')
        ->and(RedirectChain::normalize('producten/x/'))->toBe('/producten/x')
        ->and(RedirectChain::normalize(''))->toBe('/');
});

it('knows the locale prefix of a path', function () {
    $locales = ['nl', 'de', 'en'];

    expect(RedirectChain::localePrefix('/de/produkte/x', $locales))->toBe('de')
        ->and(RedirectChain::localePrefix('/producten/x', $locales))->toBeNull()
        ->and(RedirectChain::withoutLocalePrefix('/de/produkte/x', $locales))->toBe('/produkte/x')
        ->and(RedirectChain::withoutLocalePrefix('/en', $locales))->toBe('/')
        ->and(RedirectChain::withoutLocalePrefix('/producten/x', $locales))->toBeNull();
});

it('treats a case-only difference as the same address, like MySQL does', function () {
    expect(RedirectChain::resolve('/de/produkte/myraka-Pflaume', lookupFrom(['/de/produkte/myraka-Pflaume' => '/de/produkte/myraka-pflaume'])))->toBeNull()
        ->and(RedirectChain::same('/X/', 'https://lovora.nl/x'))->toBeTrue();
});
