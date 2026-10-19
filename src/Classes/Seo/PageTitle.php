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

        if ($title === trim($siteName) || str_ends_with($title, $suffix)) {
            return $title;
        }

        return $title . $suffix;
    }
}
