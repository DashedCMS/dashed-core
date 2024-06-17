<?php

namespace Dashed\DashedCore;

use Filament\Forms\Components\Builder;
use Illuminate\Support\Facades\Route;

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
            'dashed' => 'Dashed'
        ],
    ];

    public function builder(string $name, null|string|array $blocks = null): self|array
    {
        if (!$blocks) {
            return static::$builders[$name] ?? [];
        }

        static::$builders[$name] = $blocks;

        return $this;
    }

    public function getFilamentBuilderBlock(string $name = 'content', string $blocksName = 'blocks'): Builder
    {
        return Builder::make($name)
            ->blocks(cms()->builder($blocksName))
            ->collapsible(true)
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
            'hasResults' => collect($results)->filter(fn($result) => $result['hasResults'])->count() > 0,
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
