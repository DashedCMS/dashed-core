<?php

namespace Dashed\DashedCore\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Dashed\DashedCore\Models\PersonalDataRequest;
use Dashed\DashedCore\Filament\Resources\PersonalDataRequestResource\Pages\ViewPersonalDataRequest;
use Dashed\DashedCore\Filament\Resources\PersonalDataRequestResource\Pages\ListPersonalDataRequests;
use Dashed\DashedCore\Filament\Resources\PersonalDataRequestResource\Pages\CreatePersonalDataRequest;

/**
 * AVG-verzoeken: inzage en verwijdering per e-mailadres. Aanmaken maakt
 * meteen een droog overzicht; uitvoeren is een aparte, bevestigde stap op de
 * weergavepagina. Bewerken en verwijderen bestaan niet: een verzoek is bewijs.
 */
class PersonalDataRequestResource extends Resource
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesResourceLabels;

    protected static ?string $model = PersonalDataRequest::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-shield-check';

    protected static string | UnitEnum | null $navigationGroup = 'Gebruikers';

    protected static ?int $navigationSort = 130;

    protected static ?string $navigationLabel = 'AVG-verzoeken';

    protected static ?string $label = 'AVG-verzoek';

    protected static ?string $pluralLabel = 'AVG-verzoeken';

    protected static bool $isGloballySearchable = false;

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel);
    }

    // Gate::before laat een superadmin overal door, ook langs de policy; een
    // verzoek bewerken of weggooien mag ook hij niet.
    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function canErase(): bool
    {
        return (bool) auth()->user()?->can('erase_personal_data');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('email')
                ->label(__('E-mailadres'))
                ->email()
                ->required()
                ->maxLength(255),
            Select::make('type')
                ->label(__('Soort verzoek'))
                ->options(fn () => static::canErase()
                    ? PersonalDataRequest::typeLabels()
                    : [PersonalDataRequest::TYPE_EXPORT => PersonalDataRequest::typeLabels()[PersonalDataRequest::TYPE_EXPORT]])
                ->default(PersonalDataRequest::TYPE_EXPORT)
                ->in(fn () => static::canErase()
                    ? [PersonalDataRequest::TYPE_EXPORT, PersonalDataRequest::TYPE_ERASE]
                    : [PersonalDataRequest::TYPE_EXPORT])
                ->required(),
            DatePicker::make('requested_at')
                ->label(__('Datum van het verzoek'))
                ->helperText(__('Wanneer de persoon het verzoek deed. De wettelijke termijn is een maand.'))
                ->default(now())
                ->required(),
            Select::make('verification_method')
                ->label(__('Hoe is vastgesteld dat het om deze persoon gaat?'))
                ->options(fn () => PersonalDataRequest::verificationLabels())
                ->required(),
            Textarea::make('verification_note')
                ->label(__('Toelichting op de controle'))
                ->helperText(__('Bijvoorbeeld het kanaal en de datum van het verzoek. Zet hier geen kopie van een identiteitsbewijs in.'))
                ->rows(3),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('E-mailadres'))
                    ->placeholder(__('Verwijderd (alleen hash)'))
                    ->searchable(),
                TextColumn::make('type')
                    ->label(__('Soort'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => PersonalDataRequest::typeLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => $state === PersonalDataRequest::TYPE_ERASE ? 'danger' : 'info'),
                TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => PersonalDataRequest::statusLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => PersonalDataRequest::statusColors()[$state] ?? 'gray'),
                TextColumn::make('requested_at')
                    ->label(__('Verzoek gedaan op'))
                    ->date('d-m-Y')
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label(__('Aangemaakt door'))
                    ->placeholder('-'),
                TextColumn::make('completed_at')
                    ->label(__('Afgehandeld op'))
                    ->dateTime('d-m-Y H:i')
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('Soort'))
                    ->options(fn () => PersonalDataRequest::typeLabels()),
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(fn () => PersonalDataRequest::statusLabels()),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPersonalDataRequests::route('/'),
            'create' => CreatePersonalDataRequest::route('/create'),
            'view' => ViewPersonalDataRequest::route('/{record}'),
        ];
    }
}
