<?php

namespace Dashed\DashedCore\Performance\WebVitals;

class UrlNormalizer
{
    /** @var array<int, string> Paths whose first dynamic segment should be replaced with `*` */
    protected const DYNAMIC_PARENTS = [
        'products',
        'product',
        'categories',
        'category',
        'collections',
        'blog',
        'pages',
        'account/orders',
        'account/invoices',
        'bestelling',
    ];

    public static function normalize(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH) ?? $url;
        $path = '/' . ltrim($path, '/');

        if (str_starts_with($path, '/dashed')) {
            return '';
        }

        if ($path === '/') {
            return '/';
        }

        foreach (self::DYNAMIC_PARENTS as $parent) {
            $prefix = '/' . $parent . '/';
            if (str_starts_with($path, $prefix)) {
                return $prefix . '*';
            }
        }

        return $path;
    }
}
