<?php

namespace Dashed\DashedCore\Filament\Concerns;

use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Resources\Pages\CreateRecord;
use Ramsey\Uuid\Rfc4122\Fields;

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

        //Todo: if cant get this to work, do it in the create/edit model, create/use a trait for it and make sure you can extend it just like you did with the booted/saved method
//        return [
//            Repeater::make('customBlocks')
//                ->hiddenLabel()
//                ->deletable(false)
//                ->schema($schema)
////                ->schema([
////                    Tabs::make('tab')->tabs($tabs)
////                ])
//                ->maxItems(1)
//                ->defaultItems(1)
//                ->columns(2)
//                ->columnSpanFull()
//                ->visible(fn($livewire) => count($schema))
//                ->relationship('customBlocks')
////                ->mutateRelationshipDataBeforeCreateUsing(function ($data, $livewire) {
////                    $blocks = [];
////                    foreach ($data as $key => $item) {
////                        $blocks[$key] = $item;
////                        unset($data[$key]);
////                    }
////                    $data['blocks'] = $blocks;
////                    $data['koekwous'] = 'klopt';
////
////                    return $data;
////                })
////                ->mutateRelationshipDataBeforeSaveUsing(function ($data, $livewire) {
////                    $blocks = $livewire->record->blocks ?: [];
////                    foreach ($data as $key => $item) {
////                        $blocks[$key] = $item;
////                        unset($data[$key]);
////                    }
////                    $data['blocks'] = $blocks;
////                    $data['koekwous'] = 'klopt';
////
////                    return $data;
////                })
//                ->saveRelationshipsUsing(function ($livewire, $state) {
////                    dd($livewire, $state);
////                    $state = $state[array_key_first($state)] ?? [];
//                    if (!$livewire->record->customBlocks) {
//                        $livewire->record->customBlocks()->create([]);
//                        $livewire->record->refresh();
//                    }
//                    $customBlocks = $livewire->record->customBlocks;
//                    foreach ($state[array_key_first($state)] as $key => $item) {
//                        $blocks[$key] = $item;
//                    }
//                    $customBlocks->setTranslation('blocks', $livewire->activeLocale, $blocks);
//                    $customBlocks->save();
////                    $customBlocks->refresh();
////                    $livewire->record->refresh();
////                    return $customBlocks;
//                })
//                ->mutateRelationshipDataBeforeFillUsing(function ($data) {
//                    if (is_array($data['blocks'])) {
//                        foreach ($data['blocks'] ?? [] as $key => $item) {
//                            $data[$key] = $item;
//                        }
//                    }
//
//                    return $data;
//                }),
//        ];

//        foreach (Locales::getLocalesArray() as $localeKey => $locale) {
//            $newSchema = $schema;
////            foreach ($newSchema ?? [] as $field) {
////                dd($field);
//////                dump($field, $field->getStatePath() ?? 'tets');
////                $field->statePath(fn() => 'blocks.' . $localeKey . '.' . $field->getStatePath());
////            }
//
//            $tabs[] = Tab::make($localeKey)
//                ->schema($newSchema)
//                ->statePath('blocks.' . $localeKey)
//            ->saveRelationshipsUsing(function ($livewire, $state, $field) {
//                dd($field);
//                dump($state, $livewire->activeLocale, $livewire->getStatePath());
//                unset($state['id']);
//                unset($state['blockable_type']);
//                unset($state['blockable_id']);
//                unset($state['created_at']);
//                unset($state['updated_at']);
//                if (!$livewire->record->customBlocks) {
//                    $livewire->record->customBlocks()->create([]);
//                    $livewire->record->refresh();
//                }
////                $customBlocks = $livewire->record->customBlocks;
////                $customBlocks->setTranslation('blocks', $livewire->activeLocale, $state);
////                $customBlocks->save();
//            });
//        }
//        dd('asdf');

//        dd($tabs);

//        $groups = [];
//        foreach (Locales::getLocalesArray() as $localeKey => $locale) {
//                    $groups[] = Fieldset::make()
//                        ->statePath($localeKey)
//                        ->schema($schema);
//        }
//
//        return $groups;

        return [
            Fieldset::make('customBlocks')
                ->label('Custom blocks')
//                ->schema([Tabs::make('tabs')->tabs($tabs)])
                ->schema($schema)
                ->columns(2)
                ->columnSpanFull()
//                ->visible(fn($livewire) => count($schema) && !$livewire instanceof CreateRecord)
                ->statePath('customBlocks')
//                ->relationship('customBlocks')
//                ->mutateRelationshipDataBeforeCreateUsing(function ($data, $livewire) {
//                    $blocks = [];
//                    foreach ($data as $key => $item) {
//                        $blocks[$key] = $item;
//                        unset($data[$key]);
//                    }
//                    $data['blocks'][$livewire->activeLocale] = $blocks;
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
////                    $data['blocks'][$livewire->activeLocale] = $blocks;
//
//                    return $data;
//                })
//                ->mutateRelationshipDataBeforeFillUsing(function ($data) {
//
//                    if (is_array($data['blocks'])) {
//                        foreach ($data['blocks'] ?? [] as $key => $item) {
//                            $data[$key] = $item;
//                        }
//                    }
//
//                    return $data;
//                })
//                ->saveRelationshipsUsing(function ($livewire, $state) {
//                    unset($state['id']);
//                    unset($state['blocks']);
//                    unset($state['blockable_type']);
//                    unset($state['blockable_id']);
//                    unset($state['created_at']);
//                    unset($state['updated_at']);
////                    dd($state);
//                    if (!$livewire->record->customBlocks) {
//                        $livewire->record->customBlocks()->create([]);
//                        $livewire->record->refresh();
//                    }
//                    $customBlocks = $livewire->record->customBlocks;
////                    foreach ($state[array_key_first($state)] as $key => $item) {
////                        $blocks[$key] = $item;
////                    }
//                    $customBlocks->setTranslation('blocks', $livewire->activeLocale, $state);
//                    $customBlocks->save();
//                }),
        ];
    }
}
