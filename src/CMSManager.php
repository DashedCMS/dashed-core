<?php

namespace Dashed\DashedCore;

use Filament\Forms\Get;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Builder;
use Dashed\DashedCore\Models\GlobalBlock;
use Filament\Forms\Components\Actions\Action;

class CMSManager
{
    protected static $builders = [
        'sites' => [],
        'forms' => [],
        'blocks' => [],
        'content' => [],
        'routeModels' => [],
        'settingPages' => [],
        'frontendMiddlewares' => [],
        'themes' => [
            'dashed' => 'Dashed',
        ],
    ];

    public function builder(string $name, null|string|array $blocks = null): self|array
    {
        if (! $blocks) {
            return static::$builders[$name] ?? [];
        }

        static::$builders[$name] = $blocks;

        return $this;
    }

    public function getFilamentBuilderBlock(string $name = 'content', string $blocksName = 'blocks', bool $globalBlockChooser = true): Builder
    {
        return Builder::make($name)
            ->blocks(array_merge([
                Builder\Block::make('globalBlock')
                    ->label('Globaal blok')
                    ->visible(GlobalBlock::count() > 0)
                    ->schema([
                        Select::make('globalBlock')
                            ->label('Globaal blok')
                            ->options(GlobalBlock::all()->mapWithKeys(fn ($block) => [$block->id => $block->name]))
                            ->placeholder('Kies een globaal blok')
                            ->hintAction(
                                Action::make('editGlobalBlock')
                                ->label('Bewerk globaal blok')
                                ->url(fn (Get $get) => route('filament.dashed.resources.global-blocks.edit', ['record' => $get('globalBlock')]))
                                ->openUrlInNewTab()
                                ->visible(fn (Get $get) => $get('globalBlock'))
                            )
                            ->reactive()
                            ->required()
                            ->searchable()
                            ->preload()
                            ->lazy()
                            ->reactive()
                            ->columnSpanFull(),
                    ]),
            ], cms()->builder($blocksName)))
            ->collapsible(true)
            ->blockIcons()
            ->blockNumbers()
            ->blockPickerColumns(3)
            ->blockLabels()
            ->cloneable()
            ->reorderable()
            ->columnSpanFull();
    }

    public function getSearchResults(?string $query): array
    {
        $results = [];

        if ($query) {
            foreach (static::builder('routeModels') as $model) {
                $queryResults = $model['class']::search($query)->get();
                $results[$model['class']] = array_merge($model, [
                    'results' => $queryResults,
                    'count' => $queryResults->count(),
                    'hasResults' => $queryResults->count() > 0,
                ]);
            }
        }

        return [
            'results' => $results,
            'count' => collect($results)->sum('count'),
            'hasResults' => collect($results)->filter(fn ($result) => $result['hasResults'])->count() > 0,
        ];
    }

    public function isCMSRoute(): bool
    {
        if (str(request()->url())->contains('form/post')) {
            return false;
        }

        return str(request()->url())->contains(config('filament.path')) || str(request()->url())->contains('livewire');
    }
}
