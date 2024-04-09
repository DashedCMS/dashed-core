<?php

namespace Dashed\DashedCore\Filament\Concerns;

use Filament\Forms\Components\Group;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\FileUpload;

trait HasMetadataTab
{
    protected static function metadataTab(): array
    {
        return [
            Group::make()
                ->columns(1)
                ->relationship('metadata')
                ->schema([
                    TextInput::make('title')
                        ->label('Meta titel')
                        ->nullable()
                        ->minLength(5)
                        ->maxLength(70),
                    Textarea::make('description')
                        ->label('Meta omschrijving')
                        ->nullable()
                        ->minLength(5)
                        ->maxLength(170)
                        ->rows(2),
                    FileUpload::make('image')
                        ->label('Meta afbeelding')
                        ->directory('dashed/metadata')
                        ->image()
                        ->downloadable()
                        ->helperText('De beste afmeting is 1200x630 pixels'),
//                        TextInput::make('canonical_url')
//                            ->label('Meta canonical URL'),
                    Toggle::make('noindex')
                        ->label('Pagina niet indexeren'),
                ]),
            Section::make('tes')
                ->columns(1)
                ->schema([
                    TextInput::make('title')
                        ->label('Meta titel')
                        ->nullable()
                        ->minLength(5)
                        ->maxLength(70),
                ]),
        ];

        return Tab::make('Metadata')
            ->schema([
                Fieldset::make('Metadata')
                    ->columns(1)
                    ->relationship('metadata')
                    ->schema([
                        TextInput::make('title')
                            ->label('Meta titel')
                            ->nullable()
                            ->minLength(5)
                            ->maxLength(70),
                        Textarea::make('description')
                            ->label('Meta omschrijving')
                            ->nullable()
                            ->minLength(5)
                            ->maxLength(170)
                            ->rows(2),
                        FileUpload::make('image')
                            ->label('Meta afbeelding')
                            ->directory('dashed/metadata')
                            ->image()
                            ->helperText('De beste afmeting is 1200x630 pixels'),
//                        TextInput::make('canonical_url')
//                            ->label('Meta canonical URL'),
                        Toggle::make('noindex')
                            ->label('Pagina niet indexeren'),
                    ]),
            ]);
    }
}
