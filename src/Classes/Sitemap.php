<?php

namespace Dashed\DashedCore\Classes;

use DateTimeInterface;
use Spatie\Sitemap\Tags\Url;
use Spatie\Sitemap\SitemapIndex;

class Sitemap
{
    /**
     * Writes one sitemap per locale (sitemap-{locale}.xml) plus sitemap.xml as
     * the index pointing at them, so Search Console can report indexing per
     * language. sitemap.xml keeps its name: robots.txt and existing Search
     * Console registrations keep working.
     */
    public static function create()
    {
        // build() switches the app locale per URL, so the unprefixed (default)
        // locale is read from the localization config, not from app()->getLocale().
        $defaultLocale = app('laravellocalization')->getDefaultLocale();
        $siteLocales = Sites::get()['locales'] ?? [];
        $perLocale = static::splitPerLocale(static::build(), $siteLocales, $defaultLocale);

        $index = SitemapIndex::create();
        $baseUrl = rtrim((string) config('app.url'), '/');

        foreach ($perLocale as $locale => $localeSitemap) {
            $fileName = "sitemap-{$locale}.xml";
            $localeSitemap->writeToFile(public_path($fileName));
            $index->add("{$baseUrl}/{$fileName}");
        }

        $index->writeToFile(public_path('sitemap.xml'));
    }

    /**
     * One <url> per model per site locale, with lastmod, priority, the images
     * of the model and an xhtml:link alternate per locale (plus x-default),
     * so a search engine sees the translations as one page in several
     * languages instead of as unrelated URLs. A model decides itself what it
     * contributes through sitemapLastModified(), sitemapPriority() and
     * sitemapImages() (defaults in IsVisitable); a class with a static
     * getSitemapUrls() builds its own entries.
     */
    public static function build(): \Spatie\Sitemap\Sitemap
    {
        $sitemap = \Spatie\Sitemap\Sitemap::create();
        $activeLocale = app()->getLocale();
        $locales = static::siteLocales();

        foreach (cms()->builder('routeModels') as $routeModel) {
            $class = $routeModel['class'];

            if (method_exists($class, 'getSitemapUrls')) {
                $sitemap = $class::getSitemapUrls($sitemap);

                continue;
            }

            $query = $class::publicShowable();
            foreach (['metadata', 'customBlocks'] as $relation) {
                if (method_exists($class, $relation)) {
                    $query->with($relation);
                }
            }

            foreach ($query->get() as $model) {
                if ($model->metadata->noindex ?? false) {
                    continue;
                }

                foreach (static::urlsFor($model, $locales) as $tag) {
                    $sitemap->add($tag);
                }
            }
        }

        Locales::setLocale($activeLocale);

        return $sitemap;
    }

    /**
     * The Url tags of one model: one per locale in which it has a usable URL,
     * each carrying the full set of alternates. A model with a URL in one
     * locale only gets no alternates: a single hreflang says nothing.
     *
     * @return Url[]
     */
    public static function urlsFor($model, ?array $locales = null): array
    {
        $locales ??= static::siteLocales();

        // Read the shared fields in the first locale, before the URL loop
        // switches locales: the metadata image is translatable, and in a
        // locale without its own translation it reads as empty.
        if ($locales !== []) {
            Locales::setLocale($locales[0]['id']);
        }
        $lastModified = method_exists($model, 'sitemapLastModified') ? $model->sitemapLastModified() : $model->updated_at;
        $priority = method_exists($model, 'sitemapPriority') ? $model->sitemapPriority() : null;
        $images = method_exists($model, 'sitemapImages') ? $model->sitemapImages() : [];

        $urls = [];
        foreach ($locales as $locale) {
            Locales::setLocale($locale['id']);
            $url = (string) $model->getUrl($locale['id']);

            if (! static::isUsableUrl($url)) {
                continue;
            }

            // A product without overview page resolves to the root; only the
            // homepage itself belongs at the root in the sitemap.
            if (trim((string) parse_url($url, PHP_URL_PATH), '/') === '' && ! ($model->is_home ?? false)) {
                continue;
            }

            // Absolute here and not via url() in the template: url('/') drops
            // the trailing slash, and the homepage canonical keeps it.
            $urls[$locale['id']] = str_starts_with($url, '/') ? rtrim(url('/'), '/') . $url : $url;
        }

        if ($urls === []) {
            return [];
        }

        $defaultLocaleId = array_key_first($urls);

        $tags = [];
        foreach ($urls as $localeId => $url) {
            $tag = Url::create($url);

            if ($lastModified instanceof DateTimeInterface) {
                $tag->setLastModificationDate($lastModified);
            }

            if ($priority !== null) {
                $tag->setPriority((float) $priority);
            }

            foreach ($images as $image) {
                $tag->addImage($image);
            }

            if (count($urls) > 1) {
                foreach ($locales as $locale) {
                    if (isset($urls[$locale['id']])) {
                        $tag->addAlternate($urls[$locale['id']], static::hreflangCode($locale));
                    }
                }
                $tag->addAlternate($urls[$defaultLocaleId], 'x-default');
            }

            $tags[] = $tag;
        }

        return $tags;
    }

    /**
     * Groups the URLs by the locale prefix in their path (/fr/..., /de/...).
     * URLs without a locale prefix belong to the default locale, which is
     * served without prefix.
     *
     * @return array<string, \Spatie\Sitemap\Sitemap>
     */
    public static function splitPerLocale(\Spatie\Sitemap\Sitemap $sitemap, array $locales, string $defaultLocale): array
    {
        $perLocale = [];
        foreach ([$defaultLocale, ...$locales] as $locale) {
            $perLocale[$locale] ??= \Spatie\Sitemap\Sitemap::create();
        }

        foreach ($sitemap->getTags() as $tag) {
            if (! $tag instanceof Url) {
                continue;
            }

            $firstSegment = explode('/', trim((string) parse_url($tag->url, PHP_URL_PATH), '/'))[0];
            $locale = in_array($firstSegment, $locales, true) ? $firstSegment : $defaultLocale;

            $perLocale[$locale]->add($tag);
        }

        return array_filter($perLocale, fn ($localeSitemap) => $localeSitemap->getTags() !== []);
    }

    /**
     * The locales of the first site, in the order of the localization config,
     * as full locale arrays (id, name, regional). The same set the head uses
     * for its hreflang links, limited to what the site actually serves.
     */
    public static function siteLocales(): array
    {
        $siteLocales = Sites::get()['locales'] ?? [];

        return array_values(array_filter(
            Locales::getLocales(),
            fn (array $locale) => in_array($locale['id'], $siteLocales),
        ));
    }

    /**
     * Same rule as the hreflang links in the head: the regional code with a
     * dash (nl-NL) when the localization config has one, else the bare id.
     */
    public static function hreflangCode(array $locale): string
    {
        return ! empty($locale['regional']) ? str_replace('_', '-', (string) $locale['regional']) : (string) $locale['id'];
    }

    /**
     * Media ids (or pasted URLs) to absolute image URLs, deduplicated and
     * capped: Google reads at most 1000 images per URL, and a product group
     * with every variant photo would be noise rather than signal.
     */
    public static function imageUrls(array $values, int $max = 10): array
    {
        $urls = [];

        foreach (\Illuminate\Support\Arr::flatten($values) as $value) {
            if (! $value || ! function_exists('mediaHelper')) {
                continue;
            }

            $media = mediaHelper()->getSingleMedia($value, 'original');
            $url = is_object($media) ? (string) ($media->url ?? '') : (string) $media;

            if (static::isUsableUrl($url)) {
                $urls[$url] = $url;
            }

            if (count($urls) >= $max) {
                break;
            }
        }

        return array_values($urls);
    }

    /**
     * A URL is usable when it is a path or an absolute URL. Product::getUrl()
     * returns the literal 'link-product-overview-page' when the shop has no
     * overview page yet, and that is not an address.
     */
    protected static function isUsableUrl(string $url): bool
    {
        return $url !== '' && (str_starts_with($url, '/') || str_starts_with($url, 'http://') || str_starts_with($url, 'https://'));
    }
}
