<?php

namespace Dashed\DashedCore\Classes\Usage;

use Throwable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\Classes\Trash\Trash;

/**
 * Waar een pagina, product, categorie of formulier nog gebruikt wordt: in een
 * menu, als link in een blok (de linkvelden van `linkHelper()` bewaren
 * `<prefix>_<routemodel>_id`), als formulier in een blok (`form`/`form_id`),
 * en als doel van een doorverwijzing. Gebruikt door de melding bovenaan een
 * bewerkpagina en door de verwijderbevestiging, zodat een verwijdering geen
 * kapotte links achterlaat zonder dat iemand het ziet.
 *
 * De blokken worden eerst grof in de database gezocht (LIKE op de sleutel) en
 * daarna in PHP precies nagelopen; de LIKE is alleen een voorfilter, de waarde
 * zelf staat als getal of als string in de JSON.
 */
class UsageFinder
{
    /** @var array<int, callable(Model): iterable<array{source: string, title: string, url: ?string}>> */
    protected static array $extensions = [];

    /** Zoveel gebruiksplekken laat de melding hooguit zien. */
    public const LIMIT = 50;

    public static function extend(callable $finder, ?string $name = null): void
    {
        if ($name === null) {
            self::$extensions[] = $finder;
        } else {
            self::$extensions[$name] = $finder;
        }
    }

    /** Of er voor dit soort record iets te zoeken valt. */
    public static function supports(Model $target): bool
    {
        return self::routeModelKey($target) !== null || self::isForm($target);
    }

    /**
     * @return Collection<int, array{source: string, title: string, url: ?string}>
     */
    public static function find(Model $target): Collection
    {
        if (! $target->exists || ! self::supports($target)) {
            return collect();
        }

        $usages = collect()
            ->merge(self::menuItems($target))
            ->merge(self::contentBlocks($target))
            ->merge(self::redirects($target));

        foreach (self::$extensions as $extension) {
            $usages = $usages->merge(rescue(fn () => collect($extension($target)), collect(), false));
        }

        return $usages->unique(fn ($usage) => $usage['source'].'|'.$usage['title'])->take(self::LIMIT)->values();
    }

    /**
     * @return array<int, array{source: string, title: string, url: ?string}>
     */
    protected static function menuItems(Model $target): array
    {
        $class = 'Dashed\\DashedMenus\\Models\\MenuItem';

        if (! class_exists($class)) {
            return [];
        }

        return rescue(fn () => $class::query()
            ->where('model', $target::class)
            ->where('model_id', $target->getKey())
            ->with('menu')
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($item) => [
                'source' => __('Menu'),
                'title' => trim(($item->menu?->name ? $item->menu->name.': ' : '').Trash::title($item)),
                'url' => $item->menu ? Trash::editUrl($item->menu) : null,
            ])
            ->all(), [], false);
    }

    /**
     * @return array<int, array{source: string, title: string, url: ?string}>
     */
    protected static function contentBlocks(Model $target): array
    {
        $keys = self::referenceKeys($target);

        if ($keys === []) {
            return [];
        }

        $found = [];

        foreach (self::contentHolders() as [$class, $column, $source]) {
            try {
                $query = $class::query();
                if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive($class), true)) {
                    $query->withoutTrashed();
                }

                $query->where(function ($query) use ($column, $keys) {
                    foreach ($keys as $key) {
                        // Grof voorfilter: een _ in de sleutel is in LIKE een
                        // jokerteken, en dat is hier goed genoeg.
                        $query->orWhere($column, 'like', '%'.$key.'"%');
                    }
                });

                foreach ($query->limit(500)->get() as $row) {
                    if ($row->is($target)) {
                        continue;
                    }

                    $data = $row->getRawOriginal($column);
                    $data = is_string($data) ? json_decode($data, true) : $data;

                    if (self::containsReference($data, $keys, $target->getKey())) {
                        $owner = method_exists($row, 'blockable') ? ($row->blockable ?? $row) : $row;
                        $found[] = [
                            'source' => $source,
                            'title' => Trash::title($owner),
                            'url' => Trash::editUrl($owner),
                        ];
                    }
                }
            } catch (Throwable) {
                continue;
            }
        }

        return $found;
    }

    /**
     * @return array<int, array{source: string, title: string, url: ?string}>
     */
    protected static function redirects(Model $target): array
    {
        if (! method_exists($target, 'getUrl')) {
            return [];
        }

        $paths = collect(rescue(fn () => \Dashed\DashedCore\Classes\Locales::getLocales(), [], false))
            ->map(fn ($locale) => rescue(fn () => $target->getUrl($locale['id'] ?? null), null, false))
            ->push(rescue(fn () => $target->getUrl(), null, false))
            ->filter(fn ($url) => is_string($url) && $url !== '')
            ->map(fn (string $url) => '/'.ltrim((string) parse_url($url, PHP_URL_PATH), '/'))
            ->unique()
            ->values()
            ->all();

        if ($paths === []) {
            return [];
        }

        return rescue(fn () => \Dashed\DashedCore\Models\Redirect::query()
            ->whereIn('to', $paths)
            ->limit(self::LIMIT)
            ->get()
            ->map(fn ($redirect) => [
                'source' => __('Doorverwijzing'),
                'title' => $redirect->from.' → '.$redirect->to,
                'url' => Trash::editUrl($redirect),
            ])
            ->all(), [], false);
    }

    /**
     * De JSON-sleutels waaronder een verwijzing naar dit record staat.
     *
     * @return array<int, string>
     */
    public static function referenceKeys(Model $target): array
    {
        $keys = [];

        if ($routeKey = self::routeModelKey($target)) {
            $keys[] = '_'.$routeKey.'_id';
        }

        if (self::isForm($target)) {
            $keys[] = '"form';
            $keys[] = '"form_id';
        }

        return $keys;
    }

    /**
     * Loopt de blokdata af: een sleutel die op `_<routemodel>_id` eindigt, of
     * `form`/`form_id` voor een formulier, met precies deze id als waarde.
     */
    public static function containsReference(mixed $data, array $keys, int|string $id): bool
    {
        if (! is_array($data)) {
            return false;
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (self::containsReference($value, $keys, $id)) {
                    return true;
                }

                continue;
            }

            if (! is_string($key) || (string) $value !== (string) $id) {
                continue;
            }

            foreach ($keys as $needle) {
                if (str_starts_with($needle, '"') ? $key === substr($needle, 1) : str_ends_with($key, $needle)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected static function routeModelKey(Model $target): ?string
    {
        foreach ((array) cms()->builder('routeModels') as $key => $routeModel) {
            if (($routeModel['class'] ?? null) === $target::class) {
                return (string) $key;
            }
        }

        return null;
    }

    protected static function isForm(Model $target): bool
    {
        return is_a($target, 'Dashed\\DashedForms\\Models\\Form');
    }

    /**
     * Modellen met blokken waarin een link of formulier kan staan.
     *
     * @return array<int, array{0: class-string<Model>, 1: string, 2: string}>
     */
    protected static function contentHolders(): array
    {
        $candidates = [
            ['Dashed\\DashedPages\\Models\\Page', 'content', __('Pagina')],
            ['Dashed\\DashedArticles\\Models\\Article', 'content', __('Artikel')],
            ['Dashed\\DashedCore\\Models\\GlobalBlock', 'content', __('Globaal blok')],
            ['Dashed\\DashedCore\\Models\\CustomBlock', 'blocks', __('Blok')],
            ['Dashed\\DashedEcommerceCore\\Models\\Product', 'content', __('Product')],
            ['Dashed\\DashedEcommerceCore\\Models\\ProductGroup', 'content', __('Productgroep')],
            ['Dashed\\DashedEcommerceCore\\Models\\ProductCategory', 'content', __('Categorie')],
            ['Dashed\\DashedPopups\\Models\\Popup', 'blocks', __('Popup')],
            ['Dashed\\DashedNewsletter\\Models\\NewsletterCampaign', 'blocks', __('Nieuwsbrief')],
        ];

        return array_values(array_filter($candidates, function (array $candidate) {
            [$class, $column] = $candidate;

            return class_exists($class) && rescue(fn () => Schema::hasColumn((new $class())->getTable(), $column), false, false);
        }));
    }
}
