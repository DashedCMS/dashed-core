<?php

namespace Qubiqx\QcommerceCore\Classes;

use Spatie\Sitemap\SitemapGenerator;

class Sitemap
{
    public static function create()
    {
        SitemapGenerator::create(url('/'))
//            ->setConcurrency(1)
            ->writeToFile(public_path('sitemap.xml'));
    }
}
