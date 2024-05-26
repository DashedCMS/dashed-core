<?php

namespace Dashed\DashedCore\Filament\Concerns;

use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Resources\Pages\CreateRecord;

trait HasCustomBlocksTab
{
    protected static function customBlocksTab(array $schema = []): array
    {
        if (!count($schema)) {
            return [];
        }

        $tabs = [];

//        //Todo: make it working with a locales array, otherwise it wont work
//        foreach (Locales::getLocalesArray() as $localeKey => $locale) {
//            $tabs[] = Section::make($locale)
//                ->schema($schema)
//                ->relationship('customBlocks')
//                ->saveRelationshipsUsing(function ($record, $data) use ($localeKey) {
//                    dd($record, $data);
//                    $blocks = $record->blocks ?: [];
//                    $blocks[$localeKey] = $relationships;
//                    $record->blocks = $blocks;
//                    $record->save();
//                })
//                ->mutateRelationshipDataBeforeCreateUsing(function ($data, $livewire) {
//                    $blocks = [];
//                    foreach ($data as $key => $item) {
//                        $blocks[$key] = $item;
//                        unset($data[$key]);
//                    }
//                    $data['blocks'] = $blocks;
//
//                    return $data;
//                })
//                ->mutateRelationshipDataBeforeSaveUsing(function ($data, $livewire) {
//                    $blocks = $livewire->record->blocks ?: [];
//                    foreach ($data as $key => $item) {
//                        $blocks[$key] = $item;
//                        unset($data[$key]);
//                    }
//                    $data['blocks'] = $blocks;
//
//                    return $data;
//                })
//                ->mutateRelationshipDataBeforeFillUsing(function ($data) {
//                    if (is_array($data['blocks'])) {
//                        foreach ($data['blocks'] ?? [] as $key => $item) {
//                            $data[$key] = $item;
//                        }
//                    }
//
//                    return $data;
//                });
//        }
//
//        return [
//            Section::make('custom blocks')
//                ->schema($tabs)
//        ];

        return [
            Repeater::make('customBlocks')
                ->hiddenLabel()
                ->deletable(false)
//                ->schema($schema)
                ->schema([
                    Tabs::make('tab')->tabs($tabs)
                ])
                ->maxItems(1)
                ->defaultItems(1)
                ->columns(2)
                ->columnSpanFull()
                ->visible(fn($livewire) => count($schema))
                ->relationship('customBlocks')
                ->mutateRelationshipDataBeforeCreateUsing(function ($data, $livewire) {
                    $blocks = [];
                    foreach ($data as $key => $item) {
                        $blocks[$key] = $item;
                        unset($data[$key]);
                    }
                    $data['blocks'] = $blocks;

                    return $data;
                })
                ->mutateRelationshipDataBeforeSaveUsing(function ($data, $livewire) {
                    $blocks = $livewire->record->blocks ?: [];
                    foreach ($data as $key => $item) {
                        $blocks[$key] = $item;
                        unset($data[$key]);
                    }
                    $data['blocks'] = $blocks;

                    return $data;
                })
                ->mutateRelationshipDataBeforeFillUsing(function ($data) {
                    if (is_array($data['blocks'])) {
                        foreach ($data['blocks'] ?? [] as $key => $item) {
                            $data[$key] = $item;
                        }
                    }

                    return $data;
                }),
        ];

        return [
            Fieldset::make()
                ->schema($schema)
                ->columns(2)
                ->columnSpanFull()
                ->visible(fn($livewire) => count($schema) && !$livewire instanceof CreateRecord)
                ->relationship('customBlocks')
//                ->mutateRelationshipDataBeforeCreateUsing(function ($data, $livewire) {
//                    ray('mutateRelationshipDataBeforeCreateUsing');
//                    $blocks = [];
//                    foreach ($data as $key => $item) {
//                        $blocks[$key] = $item;
//                        unset($data[$key]);
//                    }
//                    $data['blocks'][$livewire->activeLocale] = $blocks;
//
//                    return $data;
//                })
                ->mutateRelationshipDataBeforeSaveUsing(function ($data, $livewire) {
                    ray('mutateRelationshipDataBeforeSaveUsing');
                    $blocks = $livewire->record->blocks ?: [];
                    foreach ($data as $key => $item) {
                        $blocks[$key] = $item;
                        unset($data[$key]);
                    }
                    $data['blocks'] = $blocks;
//                    $data['blocks'][$livewire->activeLocale] = $blocks;

                    return $data;
                })
//                ->mutateRelationshipDataBeforeFillUsing(function ($data) {
//                    dump('asdf');
//                    ray('mutateRelationshipDataBeforeFillUsing');
//                    if (is_array($data['blocks'])) {
//                        foreach ($data['blocks'] ?? [] as $key => $item) {
//                            $data[$key] = $item;
//                        }
//                    }
//
//                    return $data;
//                }),
        ];
    }
}
