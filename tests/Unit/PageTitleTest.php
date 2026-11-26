<?php

declare(strict_types=1);

use Dashed\DashedCore\Classes\Seo\PageTitle;

/**
 * Dashed.nl (26-11-2026): 64 van de 104 pagina's hadden geen eigen titel, zodat Google alleen "Contact" of
 * "DashedCMS" zag. Een achtervoegsel " | Dashed" lost dat op, maar zo'n 40 titels noemen het merk al
 * ("| DASHED - Online Agency", "| Dashed Online Agency") en mogen het niet nog eens krijgen.
 */
it('appends the suffix to a bare title', function () {
    expect(PageTitle::withSuffix('Contact', ' | Dashed', 'Dashed'))->toBe('Contact | Dashed')
        ->and(PageTitle::withSuffix('Over ons', '| Lovora', 'Lovora'))->toBe('Over ons | Lovora');
});

it('does not repeat the suffix', function () {
    expect(PageTitle::withSuffix('Webshop laten maken | Dashed', ' | Dashed', 'Dashed'))
        ->toBe('Webshop laten maken | Dashed');
});

it('skips the suffix when the title already names the site, in any case', function () {
    expect(PageTitle::withSuffix('Website Laten Maken Nijmegen | DASHED - Online Agency', ' | Dashed', 'Dashed'))
        ->toBe('Website Laten Maken Nijmegen | DASHED - Online Agency')
        ->and(PageTitle::withSuffix('Wat is SSL? | Dashed Online Agency', ' | Dashed', 'Dashed'))
        ->toBe('Wat is SSL? | Dashed Online Agency')
        ->and(PageTitle::withSuffix('Dashed', ' | Dashed', 'Dashed'))->toBe('Dashed');
});

it('still appends the suffix when the site name is only part of a longer word', function () {
    expect(PageTitle::withSuffix('DashedCMS', ' | Dashed', 'Dashed'))->toBe('DashedCMS | Dashed');
});

it('returns the title unchanged without a suffix', function () {
    expect(PageTitle::withSuffix('Contact', '', 'Dashed'))->toBe('Contact')
        ->and(PageTitle::withSuffix('', ' | Dashed', 'Dashed'))->toBe('');
});
