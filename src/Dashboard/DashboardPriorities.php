<?php

namespace Dashed\DashedCore\Dashboard;

/**
 * Het register achter "Heeft aandacht nodig" op het dashboard.
 *
 * Een pakket meldt een bron aan met
 * cms()->builder('dashboardPriorities', ['sleutel' => fn () => [...]]).
 * Elke callable krijgt niets mee en geeft een lijst items terug in het
 * formaat van <x-dashed-core::aurora.priority-queue>, plus een 'sort':
 * ['title', 'sub' => ?, 'url', 'badge' => ?, 'tone' => ?, 'sort' => int].
 * De bron levert alleen wat de ingelogde gebruiker mag openen; dit register
 * controleert geen rechten.
 *
 * Een bron die faalt (ontbrekende tabel, kapotte query) valt stil weg:
 * het dashboard mag nooit op een prioriteit van een ander pakket klappen.
 */
class DashboardPriorities
{
    public const TONES = ['mint', 'blue', 'purple', 'amber', 'red', 'neutral'];

    /** @return array<int, array{key:string,title:string,sub:?string,url:string,badge:?string,tone:string,sort:int}> */
    public static function all(): array
    {
        $items = [];

        foreach ((array) cms()->builder('dashboardPriorities') as $key => $source) {
            if (! is_callable($source)) {
                continue;
            }

            $result = rescue(fn () => $source(), [], false);

            foreach ((array) $result as $index => $item) {
                $normalized = self::normalize($item, $key.'.'.$index);
                if ($normalized) {
                    $items[] = $normalized;
                }
            }
        }

        // Stabiel sorteren: gelijke sort-waarden houden hun registratievolgorde.
        $position = 0;
        $items = array_map(function ($item) use (&$position) {
            $item['_position'] = $position++;

            return $item;
        }, $items);

        usort($items, fn ($a, $b) => [$a['sort'], $a['_position']] <=> [$b['sort'], $b['_position']]);

        return array_map(function ($item) {
            unset($item['_position']);

            return $item;
        }, $items);
    }

    /** De eerste $limit items, gesorteerd. */
    public static function top(int $limit = 6): array
    {
        return array_slice(self::all(), 0, max(0, $limit));
    }

    public static function count(): int
    {
        return count(self::all());
    }

    protected static function normalize(mixed $item, string $key): ?array
    {
        if (! is_array($item)) {
            return null;
        }

        $title = trim((string) ($item['title'] ?? ''));
        $url = trim((string) ($item['url'] ?? ''));

        if ($title === '' || $url === '') {
            return null;
        }

        $tone = (string) ($item['tone'] ?? 'neutral');

        return [
            'key' => (string) ($item['key'] ?? $key),
            'title' => $title,
            'sub' => isset($item['sub']) && $item['sub'] !== '' ? (string) $item['sub'] : null,
            'url' => $url,
            'badge' => isset($item['badge']) && $item['badge'] !== '' ? (string) $item['badge'] : null,
            'tone' => in_array($tone, self::TONES, true) ? $tone : 'neutral',
            'sort' => (int) ($item['sort'] ?? 100),
        ];
    }
}
