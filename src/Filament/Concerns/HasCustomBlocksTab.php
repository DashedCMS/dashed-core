<?php

namespace Dashed\DashedCore\Filament\Concerns;

use Filament\Pages\Page;
use Dashed\DashedCore\Classes\Locales;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Fieldset;
use Filament\Infolists\Components\TextEntry;
use Dashed\DashedCore\Filament\Actions\GenerateBlockImagesAction;
use Dashed\DashedCore\Filament\Actions\GenerateContentWithAiAction;

trait HasCustomBlocksTab
{
    protected static function customBlocksTab(array|string $blocks): array
    {
        if (! is_array($blocks)) {
            $blocks = [$blocks];
        }

        cms()->activateBuilderBlockClasses();

        $schema = [];

        foreach ($blocks as $block) {
            $schema = array_merge($schema, cms()->builder($block));
        }
        //        $schema = cms()->builder($blocks);

        if (! count($schema)) {
            return [];
        }

        $primaryBlocksName = is_array($blocks) ? ($blocks[0] ?? null) : $blocks;

        return [
            Actions::make([
                GenerateContentWithAiAction::make($primaryBlocksName),
                GenerateBlockImagesAction::make(),
            ])->visible((bool) $primaryBlocksName)->columnSpanFull(),

            Fieldset::make('customBlocks')
                ->label('Maatwerk blokken')
                ->schema(array_merge($schema, [
                    TextEntry::make('savefirst')
                        ->state('Andere talen invullen werkt alleen op de bewerk pagina, sla deze eerst op')
                        ->hidden(fn ($record) => $record)
                        ->columnSpanFull(),
                ]))
                ->columns(2)
                ->columnSpanFull()
                ->relationship('customBlocks')
                ->afterStateHydrated(fn ($set, $record, $livewire) => $set('customBlocks', $record && $record->customBlocks ? $record->customBlocks->getTranslation('blocks', $livewire->getActiveSchemaLocale()) : []))
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
                ->mutateRelationshipDataBeforeSaveUsing(function (array $data, Page $livewire, $record) {
                    $record->customBlocks->setTranslation('blocks', $livewire->getActiveSchemaLocale(), $data)->save();

                    return [];
                }),
        ];
    }
}
