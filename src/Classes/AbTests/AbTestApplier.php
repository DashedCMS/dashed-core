<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Classes\AbTests;

use InvalidArgumentException;
use Dashed\DashedCore\Models\AbTest;
use Illuminate\Database\Eloquent\Model;
use Dashed\DashedCore\Classes\Caching\CacheInvalidator;

/**
 * Rondt een test af. Met winnaar B komt de data van variant B in het blok op
 * de pagina; met winnaar A blijft het blok zoals het is. In beide gevallen
 * verdwijnt de verwijzing naar de test uit het blok, in elke taal.
 */
class AbTestApplier
{
    /** Het blok met deze test in de content van het model, of null. */
    public static function locate(AbTest $test): ?array
    {
        $model = $test->model;
        if (! $model) {
            return null;
        }

        foreach (static::contentPerLocale($model) as $locale => $content) {
            foreach ($content as $index => $block) {
                if ((int) ($block['data'][AbTest::BLOCK_KEY] ?? 0) === $test->id) {
                    return ['locale' => $locale, 'index' => $index, 'block' => $block];
                }
            }
        }

        return null;
    }

    public static function apply(AbTest $test, string $winner): void
    {
        if (! in_array($winner, ['a', 'b'], true)) {
            throw new InvalidArgumentException('Winner must be a or b.');
        }

        $variantB = $test->variantBData();
        if ($winner === 'b' && $variantB === null) {
            throw new InvalidArgumentException('Variant B has no data.');
        }

        $model = $test->model;
        if ($model) {
            $changed = false;
            $perLocale = static::contentPerLocale($model);

            foreach ($perLocale as $locale => $content) {
                foreach ($content as $index => $block) {
                    if ((int) ($block['data'][AbTest::BLOCK_KEY] ?? 0) !== $test->id) {
                        continue;
                    }

                    if ($winner === 'b' && ($block['type'] ?? null) === $test->block_type) {
                        $content[$index]['data'] = $variantB;
                    } else {
                        unset($content[$index]['data'][AbTest::BLOCK_KEY]);
                    }

                    $changed = true;
                }

                $perLocale[$locale] = $content;
            }

            if ($changed) {
                static::storeContent($model, $perLocale);
                $model->save();
            }

            CacheInvalidator::forModel($model);
        }

        $test->forceFill([
            'status' => AbTest::STATUS_FINISHED,
            'winner' => $winner,
            'ended_at' => $test->ended_at ?? now(),
        ])->save();
    }

    /** @return array<string, array<int, array<string, mixed>>> */
    protected static function contentPerLocale(Model $model): array
    {
        if (method_exists($model, 'isTranslatableAttribute') && $model->isTranslatableAttribute('content')) {
            return collect($model->getTranslations('content'))
                ->map(fn ($content) => is_array($content) ? array_values($content) : [])
                ->all();
        }

        $content = $model->content;

        return ['' => is_array($content) ? array_values($content) : []];
    }

    /** @param array<string, array<int, array<string, mixed>>> $perLocale */
    protected static function storeContent(Model $model, array $perLocale): void
    {
        if (method_exists($model, 'isTranslatableAttribute') && $model->isTranslatableAttribute('content')) {
            foreach ($perLocale as $locale => $content) {
                $model->setTranslation('content', $locale, $content);
            }

            return;
        }

        $model->content = $perLocale[''] ?? [];
    }
}
