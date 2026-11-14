<?php

declare(strict_types=1);

use Dashed\DashedCore\Classes\Caching\ResponseCacheStore;

/**
 * Pagina's staan gecomprimeerd in de responsecache: een file-store ruimt verlopen bestanden niet zelf
 * op, dus tussen twee keer legen mag de map niet vollopen. Inpakken en uitpakken moet elke waarde
 * (de HTML van de middleware, of een array van een project dat er meer bij bewaart) heel laten.
 */
it('pakt html gecomprimeerd in en weer heel uit', function () {
    $html = '<html><head></head><body>' . str_repeat('<p>Vaas</p>', 2000) . '</body></html>';

    $packed = ResponseCacheStore::pack($html);

    expect($packed)->toBeArray()
        ->and(strlen(serialize($packed)))->toBeLessThan(strlen($html) / 5)
        ->and(ResponseCacheStore::unpack($packed))->toBe($html);
});

it('laat een array van een project heel', function () {
    $value = ['html' => '<p>x</p>', 'varieert' => ['wagen' => null]];

    expect(ResponseCacheStore::unpack(ResponseCacheStore::pack($value)))->toBe($value);
});

it('geeft een oude ongecomprimeerde waarde ongewijzigd terug', function () {
    expect(ResponseCacheStore::unpack('<p>oud</p>'))->toBe('<p>oud</p>')
        ->and(ResponseCacheStore::unpack(null))->toBeNull();
});

it('geeft null bij een kapotte gecomprimeerde waarde', function () {
    expect(ResponseCacheStore::unpack(['__dashed_gz' => 'geen gzip']))->toBeNull();
});
