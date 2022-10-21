<?php

namespace Qubiqx\QcommerceCore\Filament\Concerns;

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
                        TextInput::make('title')
                            ->label('Meta titel')
                            ->nullable()
                            ->minLength(5)
                            ->maxLength(70)
                            ->rules([
                                'nullable',
                                'min:5',
                                'max:70',
                            ]),
                        Textarea::make('description')
                            ->label('Meta omschrijving')
                            ->nullable()
                            ->minLength(5)
                            ->maxLength(170)
                            ->rows(2)
                            ->rules([
                                'nullable',
                                'min:5',
                                'max:170',
                            ]),
                        FileUpload::make('image')
                            ->label('Meta afbeelding')
                            ->directory('qcommerce/metadata')
                            ->image()
                            ->helperText('De beste afmeting is 1200x630 pixels'),
//                        TextInput::make('canonical_url')
//                            ->label('Meta canonical URL'),
                        Toggle::make('noindex')
                            ->label('Pagina niet indexeren'),
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
                            ->maxLength(70)
                            ->rules([
                                'nullable',
                                'min:5',
                                'max:70',
                            ]),
                        Textarea::make('description')
                            ->label('Meta omschrijving')
                            ->nullable()
                            ->minLength(5)
                            ->maxLength(170)
                            ->rows(2)
                            ->rules([
                                'nullable',
                                'min:5',
                                'max:170',
                            ]),
                        FileUpload::make('image')
                            ->label('Meta afbeelding')
                            ->directory('qcommerce/metadata')
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
