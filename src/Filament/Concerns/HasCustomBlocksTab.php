<?php

namespace Dashed\DashedCore\Filament\Concerns;

use Filament\Forms\Components\Group;
use Filament\Resources\Pages\CreateRecord;

trait HasCustomBlocksTab
{
    protected static function customBlocksTab(array $schema = []): array
    {
        return [
            Group::make()
                ->schema($schema)
                ->columns(2)
                ->columnSpanFull()
                ->visible(fn ($livewire) => count($schema) && !$livewire instanceof CreateRecord)
                ->relationship('customBlocks')
                ->mutateRelationshipDataBeforeCreateUsing(function ($data, $livewire) {
                    $blocks = [];
                    foreach ($data as $key => $item) {
                        $blocks[$key] = $item;
                        unset($data[$key]);
                    }
                    $data['blocks'][$livewire->activeLocale] = $blocks;

                    return $data;
                })
                ->mutateRelationshipDataBeforeSaveUsing(function ($data, $livewire) {
                    $blocks = $livewire->record->blocks ?: [];
                    foreach ($data as $key => $item) {
                        $blocks[$key] = $item;
                        unset($data[$key]);
                    }
                    $data['blocks'][$livewire->activeLocale] = $blocks;

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
    }
}
