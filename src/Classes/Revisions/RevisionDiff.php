<?php

namespace Dashed\DashedCore\Classes\Revisions;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\Filament\Components\BlockHeaderLabel;

/**
 * Wat er verschilt tussen twee opnames: per veld, per taal, en bij een lijst
 * builder-blokken per blok op type en samenvatting (BlockHeaderLabel), niet
 * de ruwe JSON.
 */
class RevisionDiff
{
    public const MAX_LENGTH = 160;

    /**
     * @return array<int, array{field: string, locale: ?string, old: string, new: string}>
     */
    public static function between(?array $old, array $new, Model $model): array
    {
        $rows = static::compareAttributes(
            (array) ($old['attributes'] ?? []),
            (array) ($new['attributes'] ?? []),
            static::translatable($model),
            null,
        );

        foreach (RevisionSnapshot::relationsFor($model) as $relation) {
            $oldData = (array) ($old['relations'][$relation] ?? []);
            $newData = (array) ($new['relations'][$relation] ?? []);

            if ($oldData === [] && $newData === []) {
                continue;
            }

            array_push($rows, ...static::compareAttributes(
                $oldData,
                $newData,
                static::translatable($model->{$relation}()->getRelated()),
                static::relationLabel($relation),
            ));
        }

        return $rows;
    }

    protected static function compareAttributes(array $old, array $new, array $translatable, ?string $prefix): array
    {
        $rows = [];
        $keys = array_values(array_unique(array_merge(array_keys($old), array_keys($new))));
        sort($keys);

        foreach ($keys as $key) {
            $oldValue = static::decode($old[$key] ?? null);
            $newValue = static::decode($new[$key] ?? null);

            if (static::same($oldValue, $newValue)) {
                continue;
            }

            $label = ($prefix ? $prefix . ': ' : '') . static::fieldLabel($key);

            if (in_array($key, $translatable, true) && (is_array($oldValue) || is_array($newValue))) {
                $oldValue = is_array($oldValue) ? $oldValue : [];
                $newValue = is_array($newValue) ? $newValue : [];
                $locales = array_values(array_unique(array_merge(array_keys($oldValue), array_keys($newValue))));

                foreach ($locales as $locale) {
                    if (static::same($oldValue[$locale] ?? null, $newValue[$locale] ?? null)) {
                        continue;
                    }

                    array_push($rows, ...static::valueRows($label, (string) $locale, $oldValue[$locale] ?? null, $newValue[$locale] ?? null));
                }

                continue;
            }

            array_push($rows, ...static::valueRows($label, null, $oldValue, $newValue));
        }

        return $rows;
    }

    protected static function valueRows(string $label, ?string $locale, mixed $old, mixed $new): array
    {
        if (static::isBlockList($old) || static::isBlockList($new)) {
            return static::blockRows($label, $locale, is_array($old) ? array_values($old) : [], is_array($new) ? array_values($new) : []);
        }

        return [[
            'field' => $label,
            'locale' => $locale,
            'old' => static::stringify($old),
            'new' => static::stringify($new),
        ]];
    }

    protected static function blockRows(string $label, ?string $locale, array $old, array $new): array
    {
        $rows = [];
        $count = max(count($old), count($new));

        for ($index = 0; $index < $count; $index++) {
            $oldBlock = $old[$index] ?? null;
            $newBlock = $new[$index] ?? null;

            if (static::same($oldBlock, $newBlock)) {
                continue;
            }

            $rows[] = [
                'field' => $label . ' · ' . __('blok :nummer', ['nummer' => $index + 1]),
                'locale' => $locale,
                'old' => static::blockLabel($oldBlock, $index),
                'new' => static::blockLabel($newBlock, $index),
            ];
        }

        if ($rows === [] && count($old) !== count($new)) {
            $rows[] = ['field' => $label, 'locale' => $locale, 'old' => (string) count($old), 'new' => (string) count($new)];
        }

        return $rows;
    }

    protected static function blockLabel(mixed $block, int $index): string
    {
        if (! is_array($block)) {
            return '';
        }

        $type = Str::headline((string) ($block['type'] ?? __('Blok')));
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];

        return BlockHeaderLabel::for($type, $data, $index);
    }

    protected static function isBlockList(mixed $value): bool
    {
        if (! is_array($value) || $value === [] || ! array_is_list($value)) {
            return false;
        }

        $first = $value[0];

        return is_array($first) && array_key_exists('type', $first) && array_key_exists('data', $first);
    }

    protected static function decode(mixed $value): mixed
    {
        if (is_string($value) && $value !== '' && ($value[0] === '{' || $value[0] === '[')) {
            $decoded = json_decode($value, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $value;
    }

    protected static function same(mixed $a, mixed $b): bool
    {
        $normalize = fn ($v) => $v === '' ? null : (is_int($v) || is_float($v) || is_bool($v) ? (string) (is_bool($v) ? (int) $v : $v) : $v);

        return json_encode(static::deepSort($normalize($a))) === json_encode(static::deepSort($normalize($b)));
    }

    protected static function deepSort(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(fn ($item) => static::deepSort(is_int($item) || is_float($item) ? (string) $item : $item), $value);
    }

    public static function stringify(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        if (is_bool($value)) {
            return $value ? __('Ja') : __('Nee');
        }

        if (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $text = trim(preg_replace('/\s+/', ' ', strip_tags((string) $value)));

        return Str::limit($text, static::MAX_LENGTH);
    }

    protected static function fieldLabel(string $key): string
    {
        return Str::ucfirst(str_replace('_', ' ', Str::snake($key)));
    }

    protected static function relationLabel(string $relation): string
    {
        return match ($relation) {
            'metadata' => __('Metadata'),
            'customBlocks' => __('Extra blokken'),
            default => static::fieldLabel($relation),
        };
    }

    protected static function translatable(Model $model): array
    {
        return method_exists($model, 'getTranslatableAttributes') ? array_values($model->getTranslatableAttributes()) : [];
    }
}
