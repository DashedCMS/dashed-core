<?php

namespace Dashed\DashedCore\Classes;

class Sitemap
{
    public static function create()
    {
        $sitemap = \Spatie\Sitemap\Sitemap::create();

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
