<?php

namespace Dashed\DashedCore\Classes;

use Spatie\Sitemap\Tags\Url;

class Sitemap
{
    public static function create()
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
                            if (UrlHelper::checkUrlResponseCode($url) !== 404) {
                                $sitemap
                                    ->add(Url::create($url));
                            }
                        }
                    }
                }
            }
        }

        $sitemap->writeToFile(public_path('sitemap.xml'));
    }
}
