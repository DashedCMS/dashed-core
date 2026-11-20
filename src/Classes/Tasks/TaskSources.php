<?php

namespace Dashed\DashedCore\Classes\Tasks;

use Carbon\Carbon;

/**
 * Register van takenbronnen voor "Mijn taken". Projecten en pakketten
 * registreren hun bronnen in een serviceprovider:
 *
 *     TaskSources::register(TaskSource::resource(OrderReturnResource::class, group: 'Shop'));
 *
 * Elke bron wordt los geteld; een bron die faalt of 0 geeft valt weg.
 */
class TaskSources
{
    /** @var array<string, TaskSource> */
    protected static array $sources = [];

    public static function register(TaskSource ...$sources): void
    {
        foreach ($sources as $source) {
            static::$sources[$source->key] = $source;
        }
    }

    public static function forget(string $key): void
    {
        unset(static::$sources[$key]);
    }

    public static function flush(): void
    {
        static::$sources = [];
    }

    /** @return array<string, TaskSource> */
    public static function all(): array
    {
        return static::$sources;
    }

    /**
     * De bronnen met open werk die de ingelogde gebruiker mag zien, gesorteerd
     * op groep (in volgorde van registratie) en daarbinnen op sort.
     *
     * @return array<int, array{key: string, label: string, count: int, url: ?string, icon: mixed, group: ?string, sort: int, has_items: bool}>
     */
    public static function open(): array
    {
        $groupOrder = [];
        $tiles = [];

        foreach (static::$sources as $source) {
            $groupOrder[$source->group ?? ''] ??= count($groupOrder);

            $tile = rescue(function () use ($source) {
                if (! static::isVisible($source)) {
                    return null;
                }

                $count = (int) ($source->count)();

                if ($count <= 0) {
                    return null;
                }

                return [
                    'key' => $source->key,
                    'label' => (string) ($source->label)(),
                    'count' => $count,
                    'url' => rescue(fn () => ($source->url)(), null, report: false),
                    'icon' => $source->icon ? ($source->icon)() : null,
                    'group' => $source->group,
                    'sort' => $source->sort,
                    'has_items' => $source->items !== null,
                ];
            }, null, report: false);

            if ($tile) {
                $tiles[] = $tile;
            }
        }

        usort($tiles, fn (array $a, array $b) => [$groupOrder[$a['group'] ?? ''], $a['sort']] <=> [$groupOrder[$b['group'] ?? ''], $b['sort']]);

        return $tiles;
    }

    public static function openCount(): int
    {
        return array_sum(array_column(static::open(), 'count'));
    }

    /**
     * Alle losse open records van de bronnen met `items` die de gebruiker mag zien,
     * optioneel van één bron. Een bron die faalt valt weg.
     *
     * @return array<int, array{key: string, source: string, label: string, group: ?string, title: string, description: ?string, date: ?Carbon, url: ?string}>
     */
    public static function items(?string $only = null): array
    {
        $rows = [];

        foreach (static::$sources as $source) {
            if (! $source->items || ($only !== null && $source->key !== $only)) {
                continue;
            }

            $sourceRows = rescue(function () use ($source) {
                if (! static::isVisible($source)) {
                    return [];
                }

                $label = (string) ($source->label)();
                $rows = [];

                foreach (($source->items)() as $item) {
                    $date = $item['date'] ?? null;

                    $rows[] = [
                        'key' => $source->key . '|' . ($item['id'] ?? count($rows)),
                        'source' => $source->key,
                        'label' => $label,
                        'group' => $source->group,
                        'title' => (string) ($item['title'] ?? ''),
                        'description' => $item['description'] ?? null,
                        'date' => $date ? Carbon::parse($date) : null,
                        'url' => $item['url'] ?? null,
                    ];
                }

                return $rows;
            }, [], report: false);

            array_push($rows, ...$sourceRows);
        }

        return $rows;
    }

    protected static function isVisible(TaskSource $source): bool
    {
        return ! $source->visible || ($source->visible)();
    }
}
