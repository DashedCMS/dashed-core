<?php

namespace Dashed\DashedCore\Classes\Seo;

use Spatie\SchemaOrg\BaseType;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\Models\CustomStructuredData;

/**
 * Everything the head prints as JSON-LD for the current page, in order: the
 * schemas pushed during the request (organization, webSite, product), the
 * model's own schemas (`seoSchemas()`, e.g. Article or ProfilePage), one
 * FAQPage from its FAQ blocks, its BreadcrumbList, and the structured data a beheerder applied
 * from the AI audit (CustomStructuredData). An applied suggestion whose
 * type is already on the page is skipped: the AI suggests FAQPage, Article
 * and Organization often, and the generated schema is the one that stays
 * correct when the content changes. Called by the head itself, like the
 * canonical and the robots decision, because only there the model is known.
 */
class ModelSchemas
{
    /**
     * @param  array<string, BaseType|string>  $schemas
     * @return array<string, BaseType|HtmlString|string>
     */
    public static function for(?object $model, array $schemas): array
    {
        $schemas = array_filter($schemas);
        $types = static::typesIn($schemas);

        if ($model === null) {
            return $schemas;
        }

        if (method_exists($model, 'seoSchemas')) {
            foreach ((array) $model->seoSchemas() as $key => $schema) {
                if ($schema instanceof BaseType && ! array_key_exists($key, $schemas)) {
                    $schemas[$key] = $schema;
                    $types[] = static::family($schema->getType());
                }
            }
        }

        if (! array_key_exists('faq', $schemas) && ! in_array('faqpage', $types, true)) {
            $faq = FaqSchema::for($model);

            if ($faq) {
                $schemas['faq'] = $faq;
                $types[] = 'faqpage';
            }
        }

        if (! array_key_exists('breadcrumbs', $schemas) && ! in_array('breadcrumblist', $types, true)) {
            $breadcrumbs = BreadcrumbSchema::for($model);

            if ($breadcrumbs) {
                $schemas['breadcrumbs'] = $breadcrumbs;
                $types[] = 'breadcrumblist';
            }
        }

        foreach (static::customStructuredData($model) as $row) {
            $decoded = json_decode((string) $row->json_ld, true);

            if (! is_array($decoded) || $decoded === []) {
                continue;
            }

            $rowTypes = static::typesOf($decoded);

            if ($rowTypes === [] || array_intersect($rowTypes, $types) !== []) {
                continue;
            }

            $schemas['custom:' . $row->id] = new HtmlString(
                '<script type="application/ld+json">' . json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>'
            );
            $types = array_merge($types, $rowTypes);
        }

        return $schemas;
    }

    /**
     * @return \Illuminate\Support\Collection<int, CustomStructuredData>
     */
    protected static function customStructuredData(object $model)
    {
        if (! $model instanceof Model || ! $model->getKey()) {
            return collect();
        }

        return rescue(
            fn () => CustomStructuredData::query()
                ->where('subject_type', $model->getMorphClass())
                ->where('subject_id', $model->getKey())
                ->orderBy('sort_order')
                ->get(),
            collect(),
            false,
        );
    }

    /**
     * The lowercased @type values of the generated schemas.
     */
    protected static function typesIn(array $schemas): array
    {
        $types = [];

        foreach ($schemas as $schema) {
            if ($schema instanceof BaseType) {
                $types[] = static::family($schema->getType());
            }
        }

        return $types;
    }

    /**
     * The lowercased @type values in a decoded JSON-LD document: a single
     * node, a list of nodes, or a @graph.
     */
    public static function typesOf(array $decoded): array
    {
        $nodes = isset($decoded['@graph']) && is_array($decoded['@graph']) ? $decoded['@graph'] : (array_is_list($decoded) ? $decoded : [$decoded]);
        $types = [];

        foreach ($nodes as $node) {
            foreach ((array) ($node['@type'] ?? []) as $type) {
                if (is_string($type) && $type !== '') {
                    $types[] = static::family($type);
                }
            }
        }

        return array_values(array_unique($types));
    }

    /**
     * Subtypes count as their parent for the duplicate check: an
     * OnlineStore on the page makes a suggested Organization a duplicate,
     * and a BlogPosting a suggested Article.
     */
    public static function family(string $type): string
    {
        $type = strtolower($type);

        return match ($type) {
            'onlinestore', 'localbusiness', 'store', 'corporation', 'ngo' => 'organization',
            'blogposting', 'newsarticle', 'techarticle', 'scholarlyarticle' => 'article',
            default => $type,
        };
    }
}
