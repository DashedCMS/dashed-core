<?php

namespace Qubiqx\QcommerceCore\Classes;

use Closure;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Request;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class LinkHelper
{
    public static function field($prefix = 'url_', $required = false)
    {
        $routeModels = [];
        $routeModelInputs = [];
        foreach (cms()->builder('routeModels') as $key => $routeModel) {
            $routeModels[$key] = $routeModel['name'];

            $routeModelInputs[] =
                Select::make("{$prefix}{$key}_id")
                    ->label("Kies een " . strtolower($routeModel['name']))
                    ->required($required)
                    ->options($routeModel['class']::pluck($routeModel['nameField'] ?: 'name', 'id'))
                    ->searchable()
                    ->when(fn ($get) => in_array($get('type'), [$key]));
        }

        return Group::make(array_merge([
            Select::make('type')
                ->label('Type')
                ->default('normal')
                ->options(array_merge([
                    'normal' => 'Normaal',
                ], $routeModels))
                ->reactive()
                ->required($required),
            TextInput::make('url')
                ->label('Url')
                ->required($required)
                ->placeholder('Example: https://example.com')
                ->when(fn ($get) => in_array($get('type'), ['normal'])),
        ], $routeModelInputs))
            ->columns(2);
    }
}
