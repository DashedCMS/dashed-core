<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Dashed\DashedCore\Classes\Seo\HreflangUrls;

/**
 * Op lovora.test (29-10-2026) gaf een Duitse productpagina
 * (/de/produkte/zavira-mistelgruen-20cm) hreflang-links naar de productgroep
 * (/de/produkte/zavira), als relatief pad. Google negeert relatieve hrefs, en
 * de alternatieven moeten naar dezelfde pagina wijzen als de canonical: de
 * variant zelf.
 */
const HREFLANG_LOCALES = [
    ['id' => 'nl', 'regional' => 'nl_NL'],
    ['id' => 'de', 'regional' => 'de_DE'],
    ['id' => 'en', 'regional' => null],
];

/**
 * Gedraagt zich als ec-core's Product: met forceOwnUrl false de groep
 * (relatief, zoals ProductGroup::getUrl()), met true de variant.
 */
function hreflangProduct(array $slugs = ['nl' => 'zavira-mistelgroen-20cm', 'de' => 'zavira-mistelgruen-20cm', 'en' => 'zavira-mistletoe-green-20cm']): object
{
    return new class ($slugs)
    {
        public function __construct(public array $slugs)
        {
        }

        public function getUrl($locale = null, $forceOwnUrl = false)
        {
            $prefix = ['nl' => '/producten', 'de' => '/de/produkte', 'en' => '/en/products'][$locale];

            return $forceOwnUrl
                ? 'https://lovora.test' . $prefix . '/' . $this->slugs[$locale]
                : $prefix . '/zavira';
        }

        public function isTranslatableAttribute(string $key): bool
        {
            return $key === 'slug';
        }

        public function getTranslation(string $key, string $locale, bool $useFallback = true)
        {
            return $this->slugs[$locale] ?? '';
        }
    };
}

it('wijst op een variantpagina naar de variant in elke taal, als volledige URL', function () {
    $request = Request::create('https://lovora.test/de/produkte/zavira-mistelgruen-20cm');

    expect(HreflangUrls::for($request, hreflangProduct(), HREFLANG_LOCALES, 'de'))->toBe([
        'x-default' => 'https://lovora.test/producten/zavira-mistelgroen-20cm',
        'nl-NL' => 'https://lovora.test/producten/zavira-mistelgroen-20cm',
        'de-DE' => 'https://lovora.test/de/produkte/zavira-mistelgruen-20cm',
        'en' => 'https://lovora.test/en/products/zavira-mistletoe-green-20cm',
    ]);
});

it('maakt een relatieve model-URL absoluut als die de pagina van het verzoek is', function () {
    $request = Request::create('https://lovora.test/de/produkte/zavira');

    expect(HreflangUrls::for($request, hreflangProduct(), HREFLANG_LOCALES, 'de'))->toBe([
        'x-default' => 'https://lovora.test/producten/zavira',
        'nl-NL' => 'https://lovora.test/producten/zavira',
        'de-DE' => 'https://lovora.test/de/produkte/zavira',
        'en' => 'https://lovora.test/en/products/zavira',
    ]);
});

it('slaat een taal zonder slug over', function () {
    $request = Request::create('https://lovora.test/de/produkte/zavira-mistelgruen-20cm');
    $product = hreflangProduct(['nl' => 'zavira-mistelgroen-20cm', 'de' => 'zavira-mistelgruen-20cm', 'en' => '']);

    expect(array_keys(HreflangUrls::for($request, $product, HREFLANG_LOCALES, 'de')))
        ->toBe(['x-default', 'nl-NL', 'de-DE']);
});

it('geeft geen alternatieven als het model niet deze pagina is', function () {
    // Een categorie met één product: het model geeft een andere URL terug.
    $request = Request::create('https://lovora.test/de/kategorien/vasen');

    expect(HreflangUrls::for($request, hreflangProduct(), HREFLANG_LOCALES, 'de'))->toBe([]);
});

it('geeft niets met één taal of zonder model', function () {
    $request = Request::create('https://lovora.test/producten/zavira');

    expect(HreflangUrls::for($request, hreflangProduct(), [HREFLANG_LOCALES[0]], 'nl'))->toBe([])
        ->and(HreflangUrls::for($request, null, HREFLANG_LOCALES, 'nl'))->toBe([]);
});

it('houdt de slash van de homepage zoals de canonical', function () {
    $request = Request::create('https://lovora.test/de');
    $home = new class ()
    {
        public function getUrl($locale = null, bool $native = true)
        {
            $url = $locale === 'nl' ? '/' : '/' . $locale;

            return $native ? $url : 'https://lovora.test' . $url;
        }
    };

    expect(HreflangUrls::for($request, $home, HREFLANG_LOCALES, 'de'))->toBe([
        'x-default' => 'https://lovora.test/',
        'nl-NL' => 'https://lovora.test/',
        'de-DE' => 'https://lovora.test/de',
        'en' => 'https://lovora.test/en',
    ]);
});
