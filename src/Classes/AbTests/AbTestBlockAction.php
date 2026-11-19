<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Classes\AbTests;

use Filament\Actions\Action;
use Dashed\DashedCore\Models\AbTest;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Hidden;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Builder\Block;
use Dashed\DashedCore\Filament\Components\ContentBuilder;
use Dashed\DashedCore\Filament\Resources\AbTestResource;

/**
 * De koppeling tussen de ContentBuilder en de A/B-tests: een verborgen veld
 * in elk blok (zodat het test-id een geopend blok overleeft) en de knop op
 * een blok om een test te maken of te openen.
 */
class AbTestBlockAction
{
    /** @var \WeakMap<Block, true>|null */
    protected static ?\WeakMap $attached = null;

    /**
     * Voegt aan het schema van een blok een verborgen `_ab_test` toe. Alleen
     * een gevulde waarde wordt opgeslagen, zodat blokken zonder test geen
     * extra sleutel in hun data krijgen.
     */
    public static function attachHiddenField(Block $block): void
    {
        static::$attached ??= new \WeakMap();
        if (isset(static::$attached[$block])) {
            return;
        }
        static::$attached[$block] = true;

        $original = (fn () => $this->childComponents['default'] ?? [])->call($block);

        $block->schema(function (Block $component) use ($original) {
            $components = $component->evaluate($original) ?? [];
            if (! is_array($components)) {
                return $components;
            }

            $components[] = Hidden::make(AbTest::BLOCK_KEY)
                ->dehydrated(fn ($state): bool => filled($state));

            return $components;
        });
    }

    public static function make(): Action
    {
        return Action::make('abTest')
            ->label(__('A/B-test'))
            ->tooltip(__('A/B-test'))
            ->icon('heroicon-o-beaker')
            ->iconButton()
            ->color(fn (array $arguments, ContentBuilder $component) => static::testFor($arguments, $component) ? 'primary' : 'gray')
            ->visible(function (array $arguments, ContentBuilder $component, $livewire): bool {
                $item = $component->getRawState()[$arguments['item'] ?? ''] ?? null;

                return AbTests::enabled()
                    && static::record($livewire) !== null
                    && is_array($item)
                    && ($item['type'] ?? null) !== 'globalBlock'
                    && (auth()->user()?->can('edit_ab_test') ?? false);
            })
            ->url(fn (array $arguments, ContentBuilder $component): ?string => ($test = static::testFor($arguments, $component))
                ? AbTestResource::getUrl('edit', ['record' => $test])
                : null)
            ->requiresConfirmation()
            ->modalHeading(__('A/B-test voor dit blok'))
            ->modalDescription(__('Er wordt een test gemaakt met een kopie van dit blok als variant B. Sla de pagina daarna op, anders weet het blok niet dat het getest wordt.'))
            ->modalSubmitActionLabel(__('Test maken'))
            ->action(function (array $arguments, ContentBuilder $component, $livewire) {
                $record = static::record($livewire);
                $items = $component->getRawState();
                $item = $items[$arguments['item']] ?? null;
                if (! $record || ! is_array($item)) {
                    return null;
                }

                $data = is_array($item['data'] ?? null) ? $item['data'] : [];
                unset($data[AbTest::BLOCK_KEY]);

                $test = AbTest::create([
                    'site_id' => Sites::getActive(),
                    'name' => trim((method_exists($record, 'getAttribute') ? (string) ($record->name ?? '') : '').' - '.$item['type'], ' -'),
                    'model_type' => $record->getMorphClass(),
                    'model_id' => $record->getKey(),
                    'locale' => property_exists($livewire, 'activeLocale') ? $livewire->activeLocale : app()->getLocale(),
                    'block_type' => (string) $item['type'],
                    'variant_b' => [['type' => (string) $item['type'], 'data' => $data]],
                    'split_b' => 50,
                    'goal' => AbTest::GOAL_CLICK,
                    'status' => AbTest::STATUS_DRAFT,
                ]);

                $items[$arguments['item']]['data'][AbTest::BLOCK_KEY] = $test->id;
                $component->rawState($items);

                // rawState schrijft in de Livewire-staat zelf, dus ook een
                // geopend blok ziet de waarde in zijn verborgen veld.
                Notification::make()
                    ->title(__('A/B-test aangemaakt'))
                    ->body(__('Sla de pagina op en stel daarna variant B en het doel in.'))
                    ->actions([
                        Action::make('open')
                            ->label(__('Test openen'))
                            ->url(AbTestResource::getUrl('edit', ['record' => $test]))
                            ->openUrlInNewTab(),
                    ])
                    ->success()
                    ->send();

                return null;
            });
    }

    protected static function testFor(array $arguments, ContentBuilder $component): ?AbTest
    {
        $item = $component->getRawState()[$arguments['item'] ?? ''] ?? null;
        $id = is_array($item) ? ($item['data'][AbTest::BLOCK_KEY] ?? null) : null;

        return is_numeric($id) ? AbTest::find((int) $id) : null;
    }

    protected static function record(mixed $livewire): ?Model
    {
        if (! is_object($livewire) || ! method_exists($livewire, 'getRecord')) {
            return null;
        }

        $record = $livewire->getRecord();

        return $record instanceof Model && $record->exists ? $record : null;
    }
}
