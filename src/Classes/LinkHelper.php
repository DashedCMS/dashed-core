<?php

namespace Dashed\DashedCore\Classes;

use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class LinkHelper
{
    public function field($prefix = 'url', $required = false, $label = '')
    {
        $routeModels = [];
        $routeModelInputs = [];
        foreach (cms()->builder('routeModels') as $key => $routeModel) {
            $routeModels[$key] = $routeModel['name'];

            $routeModelInputs[] =
                Select::make("{$prefix}_{$key}_id")
                    ->label('Kies een ' . strtolower($routeModel['name']))
                    ->required($required)
                    ->getSearchResultsUsing(fn(string $search): array => $routeModel['class']::where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id')->toArray())
                    ->getOptionLabelUsing(fn($value): ?string => $routeModel['class']::find($value)?->nameWithParents)
//                    ->options($routeModel['class']::pluck($routeModel['nameField'] ?: 'name', 'id'))
                    ->searchable()
                    ->visible(fn($get) => in_array($get("{$prefix}_type"), [$key]));
        }

        return Group::make(array_merge([
            Select::make("{$prefix}_type")
                ->label($label ?: ('Type voor ' . $prefix))
                ->default('normal')
                ->options(array_merge([
                    'normal' => 'Normaal',
                ], $routeModels))
                ->reactive()
                ->required($required),
            TextInput::make("{$prefix}_url")
                ->label('Url')
                ->required($required)
                ->placeholder('Example: https://example.com of /contact')
                ->visible(fn($get) => in_array($get("{$prefix}_type"), ['normal'])),
        ], $routeModelInputs))
            ->columns(2);
    }

    public function getUrl(?array $data = [], $prefix = 'url'): string
    {
        if ($prefix && isset($data["{$prefix}_type"])) {
            $prefix = "{$prefix}_";
        } else {
            $prefix = '';
        }

        if (($data["{$prefix}type"] ?? 'normal') == 'normal') {
            return $data["{$prefix}url"] ?? '#';
        }

        if (isset($data["{$prefix}type"]) && isset(cms()->builder('routeModels')[$data["{$prefix}type"]])) {
            $routeModel = cms()->builder('routeModels')[$data["{$prefix}type"]];
        }

        if (!isset($routeModel) || !$routeModel) {
            return '';
        }

        $record = $routeModel['class']::find($data["{$prefix}{$data["{$prefix}type"]}_id"]);

        return $record ? $record->getUrl() : '#';
    }

    public function getDataToSave(array $data, string $name, ?string $siteId = null): array
    {
        $dataToSave = [];

        if (!$siteId) {
            $siteId = Sites::getActive()['id'];
        }

        $name = "{$name}_{$siteId}";

        foreach ($data as $key => $value) {
            if (str($key)->startsWith($name)) {
                $key = str($key)->replace($name . '_', '')->toString();
                $dataToSave[$key] = $value;
            }
        }

        return $dataToSave;
    }

    public function isExternalUrl(array|string $url): bool
    {
        if(is_array($url)){
            $url = linkHelper()->getUrl($url);
        }

        if (!str($url)->startsWith(['http://', 'https://'])) {
            $url = 'http://' . $url;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        $appUrl = url('/');
        $parsedAppUrl = parse_url($appUrl);
        $appHost = $parsedAppUrl['host'];

        $parsedUrl = parse_url($url);
        $urlHost = $parsedUrl['host'] ?? '';

        return $urlHost !== $appHost;
    }
}
