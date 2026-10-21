<?php

namespace Dashed\DashedCore\Classes\Seo;

use Spatie\SchemaOrg\Schema;
use Spatie\SchemaOrg\BaseType;

/**
 * De BreadcrumbList van een pagina, in de head via ModelSchemas. Elke
 * ListItem draagt zijn URL als `item` (absoluut), zoals Google het wil, en
 * niet meer een geneste WebPage met relatieve URL en de naam als
 * identifier, zoals de oude body-component deed.
 */
class BreadcrumbSchema
{
    public static function for(object $model): ?BaseType
    {
        if (! method_exists($model, 'breadcrumbs')) {
            return null;
        }

        $crumbs = rescue(fn () => (array) $model->breadcrumbs(), [], false);
        $items = [];

        foreach (array_values($crumbs) as $index => $crumb) {
            $name = trim((string) ($crumb['name'] ?? ''));
            $url = trim((string) ($crumb['url'] ?? ''));

            if ($name === '') {
                continue;
            }

            $item = Schema::listItem()->position($index + 1)->name($name);

            if ($url !== '') {
                $item->item(static::absolute($url));
            }

            $items[] = $item;
        }

        if ($items === []) {
            return null;
        }

        return Schema::breadcrumbList()->itemListElement($items);
    }

    public static function absolute(string $url): string
    {
        return str_starts_with($url, '/') ? rtrim(url('/'), '/') . $url : $url;
    }
}
