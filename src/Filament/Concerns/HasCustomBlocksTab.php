<?php

namespace Qubiqx\QcommerceCore\Filament\Concerns;

use Filament\Facades\Filament;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Str;

trait HasCustomBlocksTab
{
    protected static function customBlocksTab(array $schema = []): array
    {
        return [
            Group::make()
                ->columns(1)
                ->relationship('customBlocks')
                ->schema($schema)
                ->visible(count($schema))
                ->columnSpan([
                    'default' => 1,
                    'sm' => 1,
                    'md' => 1,
                    'lg' => 1,
                    'xl' => 2,
                    '2xl' => 2,
                ])
                ->columns(2)
                ->mutateRelationshipDataBeforeCreateUsing(function ($data, $livewire) {
                    //Todo: test loading it in on the frontend
                    $blocks = [];
                    foreach ($data as $key => $item) {
                        $blocks[$key] = $item;
                        unset($data[$key]);
                    }
                    $data['blocks'][$livewire->activeFormLocale] = $blocks;

                    return $data;
                })
                ->mutateRelationshipDataBeforeSaveUsing(function ($data, $livewire) {
                    $blocks = $livewire->record->blocks ?: [];
                    foreach ($data as $key => $item) {
                        $blocks[$key] = $item;
                        unset($data[$key]);
                    }
                    $data['blocks'][$livewire->activeFormLocale] = $blocks;

                    return $data;
                })
                ->mutateRelationshipDataBeforeFillUsing(function ($data, $livewire) {
                    if (is_array($data['blocks'])) {
                        foreach ($data['blocks'] ?? [] as $key => $item) {
                            $data[$key] = $item;
                        }
                    }
                    return $data;
                })
        ];
    }
}
