<?php

namespace Dashed\DashedCore\Classes;

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

    public static function build(): \Spatie\Sitemap\Sitemap
    {
        $sitemap = \Spatie\Sitemap\Sitemap::create();

        foreach (cms()->builder('routeModels') as $routeModel) {
            if (method_exists($routeModel['class'], 'getSitemapUrls')) {
                $sitemap = $routeModel['class']::getSitemapUrls($sitemap);
            } else {
                $results = $routeModel['class']::publicShowable()->get();
                foreach ($results as $result) {
                    foreach (Locales::getLocales() as $locale) {
                        if (in_array($locale['id'], Sites::get()['locales'])) {
                            Locales::setLocale($locale['id']);
                            $url = $result->getUrl($locale['id']);
                            //Todo: create another check to see if the page is okay. This is just a quick fix. Maybe do a better check if there is a slug and name available for the item
                            //                            if (UrlHelper::checkUrlResponseCode($url) !== 404) {
                            if (! ($result->metadata->noindex ?? false)) {
                                $sitemap
                                    ->add(Url::create($url));
                            }
                            //                            }
                        }
                    }
                }
            }
        }

        return $sitemap;
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
}
