<?php

declare(strict_types=1);

use Dashed\DashedCore\Classes\Seo\SlugMaker;

/**
 * Lovora (30-10-2026): een taal zonder naam kreeg een slug van willekeurige tekens (/en/m, /en/blogs/1idw),
 * en een botsende naam een teken erachter (flexi-haai-sleutelhangerm). Search Console vond honderden van zulke
 * lege pagina's.
 */
it('takes the slug and otherwise the name', function () {
    expect(SlugMaker::bron('Eigen Slug', 'Naam'))->toBe('eigen-slug')
        ->and(SlugMaker::bron('', 'Japandi Kat'))->toBe('japandi-kat')
        ->and(SlugMaker::bron(null, '  '))->toBe('');
});

it('leaves an empty slug empty instead of inventing one', function () {
    expect(SlugMaker::uniek('', fn () => true))->toBe('');
});

it('numbers a clashing slug instead of adding random characters', function () {
    $bezet = ['flexi-haai', 'flexi-haai-2'];

    expect(SlugMaker::uniek('flexi-haai', fn (string $s) => in_array($s, $bezet, true)))->toBe('flexi-haai-3')
        ->and(SlugMaker::uniek('goudvis', fn (string $s) => in_array($s, $bezet, true)))->toBe('goudvis')
        ->and(SlugMaker::uniek('vaas-2026', fn (string $s) => $s === 'vaas-2026'))->toBe('vaas-2026-2');
});
