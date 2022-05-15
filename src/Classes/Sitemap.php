<?php

namespace Qubiqx\QcommerceCore\Classes;

use Spatie\Sitemap\SitemapGenerator;

class Sitemap
{
    public static function create()
    {
        $sitemap = \Spatie\Sitemap\Sitemap::create();

        foreach (cms()->builder('routeModels') as $routeModel) {
            $sitemap = $routeModel['routeHandler']::getSitemapUrls($sitemap);
            $sitemap->writeToFile(public_path('sitemap.xml'));
            dd('s');
        }

        $sitemap->writeToFile(public_path('sitemap.xml'));
    }
}
