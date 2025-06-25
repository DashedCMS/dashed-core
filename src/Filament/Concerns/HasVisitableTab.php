<?php

namespace Dashed\DashedCore\Filament\Concerns;

use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\Schema;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Dashed\DashedCore\Models\Customsetting;

trait HasVisitableTab
{
    protected static function metadataTab(): array
    {
        return [
            Group::make()
                ->columns([
                    'default' => 1,
                    'lg' => 6,
                ])
                ->relationship('metadata')
                ->saveRelationshipsUsing(function (array $state, $livewire, $record) {
                    $record->metadata->setlocale($livewire->getActiveFormsLocale());
                    $record->metadata->update($state);
                })
                ->schema([
                    TextInput::make('title')
                        ->label('Meta titel')
                        ->nullable()
                        ->minLength(5)
                        ->maxLength(70)
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 2,
                        ]),
                    Textarea::make('description')
                        ->label('Meta omschrijving')
                        ->nullable()
                        ->minLength(5)
                        ->maxLength(170)
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 2,
                        ]),
                    mediaHelper()->field('image', 'Meta afbeelding')
                        ->acceptedFileTypes(['image/*'])
                        ->helperText('De beste afmeting is 1200x630 pixels')
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 2,
                        ]),
                    //                        TextInput::make('canonical_url')
                    //                            ->label('Meta canonical URL'),
                    Toggle::make('noindex')
                        ->label('Pagina niet indexeren')
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 3,
                        ]),
                    TextInput::make('password')
                        ->label('Wachtwoord van deze pagina')
                        ->nullable()
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 3,
                        ]),
                    Textarea::make('head_scripts')
                        ->label('Scripts in head')
                        ->nullable()
                        ->maxLength(50000)
                        ->rows(2)
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 3,
                        ]),
                    Textarea::make('top_body_scripts')
                        ->label('Scripts in top van body')
                        ->nullable()
                        ->maxLength(50000)
                        ->rows(2)
                        ->columnSpan([
                            'default' => 1,
                            'lg' => 3,
                        ]),
                ]),
        ];
    }

    protected static function publishTab(): array
    {
        $schema = [
            Toggle::make('public')
                ->label('Openbaar')
                ->helperText('Indien je dit item niet openbaar maakt, is het enkel zichtbaar voor beheerders')
                ->default(true)
                ->columnSpanFull(),
            DatePicker::make('start_date')
                ->label('Vul een startdatum in voor dit item:')
                ->helperText('Indien je geen startdatum opgeeft, is het item direct zichtbaar')
                ->nullable()
                ->date(),
            DatePicker::make('end_date')
                ->label('Vul een einddatum in voor dit item:')
                ->helperText('Indien je geen einddatum opgeeft, vervalt het item niet')
                ->nullable()
                ->date()
                ->after('startDate'),
            Select::make('site_ids')
                ->label('Actief op sites')
                ->options(collect(Sites::getSites())->pluck('name', 'id'))
                ->multiple()
                ->hidden(fn () => ! (Sites::getAmountOfSites() > 1))
                ->required(),
        ];

        if (method_exists(self::$model, 'parent') && self::$model::canHaveParent()) {
            $schema[] =
                Select::make('parent_id')
                    ->relationship('parent', 'name')
                    ->options(fn ($record) => self::$model::where('id', '!=', $record->id ?? 0)->pluck('name', 'id'))
                    ->searchable()
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
        if (method_exists(self::$model, 'parent') && Schema::hasColumn(app(self::$model)->getTable(), 'parent_id')) {
            $schema[] =
                TextColumn::make('parent.name')
                    ->label('Bovenliggende item')
                    ->sortable();
        }
        $schema[] =
            TextColumn::make('site_ids')
                ->label('Actief op sites')
                ->sortable()
                ->badge()
                ->hidden(! (Sites::getAmountOfSites() > 1))
                ->searchable();
        $schema[] = IconColumn::make('status')
            ->label('Status')
            ->trueIcon('heroicon-o-check-circle')
            ->falseIcon('heroicon-o-x-circle');
        $schema[] = TextColumn::make('created_at')
            ->label('Aangemaakt op')
            ->sortable()
            ->dateTime();

        if (Customsetting::get('seo_check_models', null, false)) {
            $schema[] = TextColumn::make('seo_score')
                ->label('SEO score')
                ->getStateUsing(fn ($record) => $record->getActualScore());
        }

        return $schema;
    }
}
