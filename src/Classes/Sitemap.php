<?php

namespace Dashed\DashedCore\Classes;

class Sitemap
{
    public static function create()
    {
        $sitemap = \Spatie\Sitemap\Sitemap::create();

        //Todo: rebuild this to receive the model results, and then just add them to the sitemap
        foreach (cms()->builder('routeModels') as $routeModel) {
            if (method_exists($routeModel['class'], 'getSitemapUrls')) {
                $sitemap = $routeModel['class']::getSitemapUrls($sitemap);
            } else {
                $sitemap = $routeModel['routeHandler']::getSitemapUrls($sitemap);
            }
            $sitemap->writeToFile(public_path('sitemap.xml'));
        }

        $sitemap->writeToFile(public_path('sitemap.xml'));
    }
}
