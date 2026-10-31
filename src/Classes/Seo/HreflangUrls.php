<?php

namespace Dashed\DashedCore\Classes\Seo;

use Illuminate\Http\Request;

/**
 * De hreflang-alternatieven van een pagina: dezelfde pagina in elke taal,
 * als volledige URL (Google negeert relatieve hrefs).
 *
 * Dezelfde regel als CanonicalUrl: een URL van het model telt alleen als hij
 * dezelfde pagina is als het verzoek. Een product dat naar zijn productgroep
 * verwijst (only_show_parent_product) geeft met getUrl($locale, false) de
 * groep terug, terwijl het verzoek (en dus de canonical) de variant is; dan
 * wordt de eigen URL van het model gebruikt (getUrl($locale, true), bij een
 * product "forceOwnUrl"). Klopt geen van beide met het verzoek, dan weten we
 * niet welke URL deze pagina in een andere taal heeft en komen er geen
 * alternatieven.
 */
class HreflangUrls
{
    /**
     * @param  array<int, array{id: string, regional?: string|null}>  $locales  eerste = standaardtaal (x-default)
     * @return array<string, string> hreflang-code => absolute URL, met x-default eerst
     */
    public static function for(Request $request, ?object $model, array $locales, string $currentLocale): array
    {
        if (! $model || ! method_exists($model, 'getUrl') || count($locales) < 2) {
            return [];
        }

        $variant = self::matchingVariant($request, $model, $currentLocale);
        if ($variant === null) {
            return [];
        }

        $urls = [];
        foreach ($locales as $locale) {
            if (! self::hasSlug($model, $locale['id'])) {
                continue;
            }

            $code = ! empty($locale['regional']) ? str_replace('_', '-', $locale['regional']) : $locale['id'];
            $urls[$code] = self::absolute($request, (string) $model->getUrl($locale['id'], $variant));
        }

        // x-default = de standaardtaal, alleen als die zelf een alternatief heeft.
        $firstCode = ! empty($locales[0]['regional']) ? str_replace('_', '-', $locales[0]['regional']) : $locales[0]['id'];
        if (count($urls) < 2) {
            return [];
        }
        if (! isset($urls[$firstCode])) {
            return $urls;
        }

        return ['x-default' => $urls[$firstCode]] + $urls;
    }

    /**
     * Het tweede argument van getUrl() waarmee het model de pagina van het
     * verzoek teruggeeft, of null als geen van beide dat doet.
     */
    protected static function matchingVariant(Request $request, object $model, string $currentLocale): ?bool
    {
        foreach ([false, true] as $variant) {
            $url = self::absolute($request, (string) $model->getUrl($currentLocale, $variant));
            if (self::samePage($url, $request->url())) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * Een vertaalbare slug zonder waarde in die taal geeft een kapotte URL
     * (bijvoorbeeld het overzicht zelf); die taal krijgt geen alternatief.
     */
    public static function hasSlug(object $model, string $locale): bool
    {
        if (! method_exists($model, 'isTranslatableAttribute') || ! $model->isTranslatableAttribute('slug')) {
            return true;
        }

        return trim((string) $model->getTranslation('slug', $locale, false)) !== '';
    }

    protected static function absolute(Request $request, string $url): string
    {
        if (! str_starts_with($url, 'http')) {
            $url = rtrim($request->root(), '/') . '/' . ltrim($url, '/');
        }

        // Zoals de canonical: de homepage eindigt op een slash, de rest niet.
        $path = (string) parse_url($url, PHP_URL_PATH);

        return $path === '' || $path === '/' ? rtrim($url, '/') . '/' : rtrim($url, '/');
    }

    protected static function samePage(string $a, string $b): bool
    {
        $normalize = fn (string $url) => strtolower(trim((string) parse_url($url, PHP_URL_PATH), '/'));

        return $normalize($a) === $normalize($b);
    }
}
