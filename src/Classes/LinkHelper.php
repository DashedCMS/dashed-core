<?php

namespace Dashed\DashedCore\Classes;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Dashed\DashedCore\Filament\Components\LinkRouteModelGroup;
use Dashed\DashedCore\Classes\QueryHelpers\RelationshipSearchQuery;

class LinkHelper
{
    /** @var array<string, ?string> */
    protected static array $labelMemo = [];

    public static function labelFor(string $class, mixed $id): ?string
    {
        if (blank($id)) {
            return null;
        }

        return static::$labelMemo["{$class}:{$id}"] ??= $class::find($id)?->nameWithParents;
    }

    public static function flushLabelMemo(): void
    {
        static::$labelMemo = [];
    }

    public function field($prefix = 'url', $required = false, $label = '')
    {
        $routeModels = [];
        foreach (cms()->builder('routeModels') as $key => $routeModel) {
            $routeModels[$key] = $routeModel['name'];
        }

        // Filaments resolveRelativeKey() strip per "../" één puntsegment; een
        // prefix met een punt erin (linkHelper()->field('url.url')) liet
        // "../{$prefix}_link" daardoor op een niet-bestaande sleutel uitkomen.
        // Punten in de groepssleutel zelf omzeilen dat.
        $groupKey = str_replace('.', '_', $prefix) . '_link';
        $modelGroupKey = "{$groupKey}_model";

        return Group::make([
            Select::make("{$prefix}_type")
                ->label($label ?: __('Type voor :prefix', ['prefix' => $prefix]))
                ->default('normal')
                ->options(array_merge([
                    'normal' => 'Normaal',
                ], $routeModels))
                ->live()
                // De keuzelijst van het nieuwe type begint leeg, zoals
                // Filament het voorschrijft voor een dynamisch schema.
                ->afterStateUpdated(fn (Select $component) => $component
                    ->getContainer()
                    ->getComponent($modelGroupKey)
                    ?->getChildSchema()
                    ->fill())
                ->partiallyRenderComponentsAfterStateUpdated(["../{$groupKey}"])
                ->required($required),
            TextInput::make("{$prefix}_url")
                ->label(__('Url'))
                ->required($required)
                ->placeholder(__('Example: https://example.com of /contact'))
                ->visible(fn ($get) => in_array($get("{$prefix}_type"), ['normal'])),
            // Alleen de keuzelijst van het gekozen type, niet een verborgen
            // keuzelijst per routemodel. Linkvelden staan in repeateritems in
            // builderblokken, en bij elk verzoek bouwde en toetste Filament
            // die tientallen verborgen velden per linkveld opnieuw. Waarom
            // dit een eigen klasse is: zie LinkRouteModelGroup.
            LinkRouteModelGroup::make()
                ->linkPrefix($prefix)
                ->routeModelKeys(array_keys($routeModels))
                ->schema(fn (Get $get): array => $this->routeModelSelect($prefix, $get("{$prefix}_type"), $required))
                ->key($modelGroupKey),
        ])
            ->key($groupKey)
            ->columnSpanFull()
            ->columns(2);
    }

    /**
     * @return array<Select>
     */
    protected function routeModelSelect(string $prefix, mixed $type, mixed $required): array
    {
        $routeModel = is_string($type) ? (cms()->builder('routeModels')[$type] ?? null) : null;

        if (! $routeModel) {
            return [];
        }

        return [
            Select::make("{$prefix}_{$type}_id")
                ->label(__('Kies een :naam', ['naam' => strtolower($routeModel['name'])]))
                ->required($required)
                ->getSearchResultsUsing(fn (string $search): array => RelationshipSearchQuery::make($routeModel['class'], $search))
                ->preload()
                ->getOptionLabelUsing(fn ($value): ?string => static::labelFor($routeModel['class'], $value))
                ->searchable(),
        ];
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

        if (! isset($routeModel) || ! $routeModel) {
            return '';
        }

        $record = $routeModel['class']::find($data["{$prefix}{$data["{$prefix}type"]}_id"]);

        return $record ? $record->getUrl() : '#';
    }

    public function getDataToSave(array $data, string $name, ?string $siteId = null): array
    {
        $dataToSave = [];

        if (! $siteId) {
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
        if (is_array($url)) {
            $url = linkHelper()->getUrl($url);
        }

        if (! str($url)->startsWith(['http://', 'https://'])) {
            $url = 'http://' . $url;
        }

        if (! filter_var($url, FILTER_VALIDATE_URL)) {
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
