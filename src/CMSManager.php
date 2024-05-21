<?php

namespace Dashed\DashedCore;

use Illuminate\Support\Facades\Route;

class CMSManager
{
    protected static $models = [];

    protected static $builders = [
        'sites' => [],
        'forms' => [],
        'blocks' => [],
        'content' => [],
        'routeModels' => [],
        'settingPages' => [],
        'frontendMiddlewares' => [],
    ];

    public function model(string $name, ?string $implementation = null): self|string
    {
        if (! $implementation) {
            return static::$models[$name];
        }

        static::$models[$name] = $implementation;

        return $this;
    }

    public function builder(string $name, ?array $blocks = null): self|array
    {
        if (! $blocks) {
            return static::$builders[$name] ?? [];
        }

        static::$builders[$name] = $blocks;

        return $this;
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
        if(str(request()->url())->contains('form/post')){
            return false;
        }

        return str(request()->url())->contains(config('filament.path')) || str(request()->url())->contains('livewire');
    }
}
