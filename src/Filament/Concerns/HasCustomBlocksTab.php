<?php

namespace Dashed\DashedCore\Filament\Concerns;

use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Pages\CreateRecord;

trait HasCustomBlocksTab
{
    protected static function customBlocksTab(array $schema = []): array
    {
        return [
            Repeater::make('customBlocks')
                ->hiddenLabel()
                ->deletable(false)
                ->schema($schema)
                ->minItems(1)
                ->maxItems(1)
                ->defaultItems(1)
                ->columns(2)
                ->columnSpanFull()
                ->visible(fn ($livewire) => count($schema))
//                ->visible(fn($livewire) => count($schema) && !$livewire instanceof CreateRecord)
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
                ->visible(fn ($livewire) => count($schema) && !$livewire instanceof CreateRecord)
                ->relationship('customBlocks')
                ->mutateRelationshipDataBeforeCreateUsing(function ($data, $livewire) {
                    ray('mutateRelationshipDataBeforeCreateUsing');
                    $blocks = [];
                    foreach ($data as $key => $item) {
                        $blocks[$key] = $item;
                        unset($data[$key]);
                    }
                    $data['blocks'][$livewire->activeLocale] = $blocks;

                    return $data;
                })
                ->mutateRelationshipDataBeforeSaveUsing(function ($data, $livewire) {
                    ray('mutateRelationshipDataBeforeSaveUsing');
                    $blocks = $livewire->record->blocks ?: [];
                    foreach ($data as $key => $item) {
                        $blocks[$key] = $item;
                        unset($data[$key]);
                    }
                    $data['blocks'][$livewire->activeLocale] = $blocks;

                    return $data;
                })
                ->mutateRelationshipDataBeforeFillUsing(function ($data) {
                    ray('mutateRelationshipDataBeforeFillUsing');
                    if (is_array($data['blocks'])) {
                        foreach ($data['blocks'] ?? [] as $key => $item) {
                            $data[$key] = $item;
                        }
                    }

                    return $data;
                }),
        ];
    }
}
