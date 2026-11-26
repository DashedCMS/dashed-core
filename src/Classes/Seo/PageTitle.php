<?php

namespace Dashed\DashedCore\Classes\Seo;

class PageTitle
{
    /**
     * Achtervoegsel alleen op <title>, nooit dubbel, en niet op een titel die
     * zelf al de sitenaam is (de homepage heet vaak zo).
     */
    public static function withSuffix(string $title, string $suffix, string $siteName): string
    {
        $title = trim($title);
        $suffix = rtrim($suffix);

        if ($title === '' || $suffix === '') {
            return $title;
        }

        // Een achtervoegsel als "| Lovora" zonder voorloopspatie plakt anders
        // aan de titel: "Over ons| Lovora".
        if (! preg_match('/^\s/u', $suffix)) {
            $suffix = ' ' . $suffix;
        }

        if (str_ends_with($title, $suffix) || static::namesSite($title, $siteName)) {
            return $title;
        }

        return $title . $suffix;
    }

    /**
     * Titels die het merk al noemen ("| DASHED - Online Agency") krijgen het
     * niet nog eens. Alleen als los woord: "DashedCMS" telt niet als "Dashed".
     */
    private static function namesSite(string $title, string $siteName): bool
    {
        $siteName = trim($siteName);

        if ($siteName === '') {
            return false;
        }

        return (bool) preg_match('/(?<![\p{L}\p{N}])' . preg_quote($siteName, '/') . '(?![\p{L}\p{N}])/iu', $title);
    }
}
