<?php

namespace Dashed\DashedCore\Filament\Concerns;

use Filament\Pages\Page;
use Dashed\DashedCore\Classes\Locales;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Placeholder;

trait HasCustomBlocksTab
{
    protected static function customBlocksTab(array $schema = []): array
    {
        if (! count($schema)) {
            return [];
        }

        return [
            Fieldset::make('blocks')
                ->label('Maatwerk blokken')
                ->schema(array_merge($schema, [
                    Placeholder::make('savefirst')
                        ->label('Andere talen invullen werkt alleen op de bewerk pagina, sla deze eerst op')
                        ->hidden(fn ($record) => $record)
                        ->columnSpanFull(),
                ]))
                ->columns(2)
                ->columnSpanFull()
                ->relationship('customBlocks')
                ->afterStateHydrated(fn ($set, $record, $livewire) => $set('customBlocks', $record && $record->customBlocks ? $record->customBlocks->getTranslation('blocks', $livewire->getActiveFormsLocale()) : []))
                ->loadStateFromRelationshipsUsing(function ($set, $record, Page $livewire) {
                    if (! $record->customBlocks) {
                        $record->customBlocks()->create([]);
                        $record->refresh();
                    }

                    $blocks = json_decode($record->customBlocks->getAttributes()['blocks'], true);
                    $localeKeys = array_keys(Locales::getLocalesArray());
                    $missingKeys = array_diff($localeKeys, array_keys($blocks ?? []));
                    foreach ($missingKeys as $missingKey) {
                        $blocks[$missingKey] = [];
                    }

                    $record->customBlocks->blocks = $blocks;
                    $record->customBlocks->save();

                    $set('customBlocks', $record->customBlocks->blocks);
                })
                ->mutateRelationshipDataBeforeCreateUsing(function (array $state, $record, Page $livewire) {
                    return [];
                })
                ->saveRelationshipsUsing(function (array $state, Page $livewire, $record) {
                    $record->customBlocks->setTranslation('blocks', $livewire->getActiveFormsLocale(), $state)->save();
                }),
        ];
    }
}
