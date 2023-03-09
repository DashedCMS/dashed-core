<?php

namespace Qubiqx\QcommerceCore\Filament\Concerns;

use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Qubiqx\QcommerceCore\Classes\Sites;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;

trait HasVisitableTab
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
                        ->enableDownload()
                        ->helperText('De beste afmeting is 1200x630 pixels'),
//                        TextInput::make('canonical_url')
//                            ->label('Meta canonical URL'),
                    Toggle::make('noindex')
                        ->label('Pagina niet indexeren'),
                ]),
        ];
    }

    protected static function publishTab(): array
    {
        $schema = [
            DatePicker::make('start_date')
                ->label('Vul een startdatum in voor de pagina:')
                ->helperText('Indien je geen startdatum opgeeft, is de pagina direct zichtbaar')
                ->rules([
                    'nullable',
                    'date',
                ]),
            DatePicker::make('end_date')
                ->label('Vul een einddatum in voor de pagina:')
                ->helperText('Indien je geen einddatum opgeeft, vervalt de pagina niet')
                ->rules([
                    'nullable',
                    'date',
                    'after:startDate',
                ]),
            Select::make('site_ids')
                ->label('Actief op sites')
                ->options(collect(Sites::getSites())->pluck('name', 'id'))
                ->multiple()
                ->hidden(fn () => ! (Sites::getAmountOfSites() > 1))
                ->required(),
        ];

        if (method_exists(self::$model, 'parent')) {
            $schema[] =
                Select::make('parent_id')
                    ->relationship('parent', 'name')
                    ->options(fn ($record) => self::$model::where('id', '!=', $record->id ?? 0)->pluck('name', 'id'))
                    ->label('Bovenliggende item');
        }

        return [
            Group::make()
                ->columns(1)
                ->schema($schema),
        ];
    }

    protected static function visitableTableColumns(): array
    {
        if (method_exists(self::$model, 'parent')) {
            $schema[] =
                TextColumn::make('parent.name')
                    ->label('Bovenliggende item')
                    ->sortable();
        }
        $schema[] =
            TextColumn::make('site_ids')
                ->label('Actief op sites')
                ->sortable()
                ->hidden(! (Sites::getAmountOfSites() > 1))
                ->searchable();
        $schema[] = IconColumn::make('status')
            ->label('Status')
            ->trueIcon('heroicon-o-check-circle')
            ->falseIcon('heroicon-o-x-circle');

        return $schema;
    }
}
