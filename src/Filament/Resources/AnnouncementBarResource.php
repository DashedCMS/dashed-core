<?php

namespace Dashed\DashedCore\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Dashed\DashedCore\Classes\Sites;
use Dashed\DashedCore\Classes\Locales;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\CheckboxList;
use Dashed\DashedCore\Models\AnnouncementBar;
use Filament\Forms\Components\DateTimePicker;
use Dashed\DashedCore\Classes\AnnouncementBars\AnnouncementBars;
use Dashed\DashedCore\Filament\Pages\Settings\AnnouncementBarSettingsPage;
use Dashed\DashedCore\Filament\Resources\AnnouncementBarResource\Pages\EditAnnouncementBar;
use Dashed\DashedCore\Filament\Resources\AnnouncementBarResource\Pages\ListAnnouncementBars;
use Dashed\DashedCore\Filament\Resources\AnnouncementBarResource\Pages\CreateAnnouncementBar;

class AnnouncementBarResource extends Resource
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesResourceLabels;

    protected static ?string $model = AnnouncementBar::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-megaphone';

    protected static string | UnitEnum | null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Actiebalken';

    protected static ?string $label = 'Actiebalk';

    protected static ?string $pluralLabel = 'Actiebalken';

    protected static ?int $navigationSort = 60;

    protected static bool $isGloballySearchable = false;

    public static function form(Schema $schema): Schema
    {
        $hexRule = 'regex:/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/';
        $urlRule = fn () => function (string $attribute, $value, \Closure $fail) {
            if (filled($value) && AnnouncementBars::safeUrl((string) $value) === null) {
                $fail(__('Gebruik een pad (/actie) of een volledige URL met http(s).'));
            }
        };

        $perLocale = [];
        foreach (Locales::getLocales() as $locale) {
            $perLocale[] = Section::make(strtoupper($locale['id']) . ' - ' . $locale['name'])
                ->columns(2)
                ->schema([
                    TextInput::make('text.' . $locale['id'])
                        ->label(__('Tekst'))
                        ->maxLength(255)
                        ->columnSpanFull(),
                    TextInput::make('link_url.' . $locale['id'])
                        ->label(__('Link'))
                        ->maxLength(2048)
                        ->rules([$urlRule]),
                    TextInput::make('link_label.' . $locale['id'])
                        ->label(__('Linktekst'))
                        ->helperText(__('Leeg: de hele tekst wordt de link.'))
                        ->maxLength(100),
                ]);
        }

        return $schema->schema([
            Section::make(__('Actiebalk'))
                ->columnSpanFull()
                ->columns(2)
                ->description(__('Staat pas op de site zodra actiebalken aan staan bij Instellingen, Actiebalken.'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('Naam'))
                        ->required()
                        ->maxLength(255),
                    Select::make('site_id')
                        ->label(__('Site'))
                        ->options(collect(Sites::getSites())->pluck('name', 'id')->all())
                        ->default(Sites::getActive())
                        ->required()
                        ->visible(fn () => Sites::getAmountOfSites() > 1),
                    Toggle::make('active')
                        ->label(__('Actief'))
                        ->default(true),
                    Toggle::make('dismissible')
                        ->label(__('Bezoeker kan de balk wegklikken'))
                        ->helperText(__('Onthouden in de browser; na een wijziging aan de balk verschijnt hij weer.'))
                        ->default(true),
                    DateTimePicker::make('starts_at')
                        ->label(__('Vanaf'))
                        ->seconds(false),
                    DateTimePicker::make('ends_at')
                        ->label(__('Tot'))
                        ->seconds(false)
                        ->after('starts_at')
                        ->required(fn ($get) => (bool) $get('countdown')),
                    Toggle::make('countdown')
                        ->label(__('Countdown naar de einddatum tonen'))
                        ->live(),
                    TextInput::make('sort')
                        ->label(__('Volgorde'))
                        ->numeric()
                        ->default(0),
                    ColorPicker::make('background_color')
                        ->label(__('Achtergrondkleur'))
                        ->default(AnnouncementBars::DEFAULT_BACKGROUND)
                        ->rules([$hexRule]),
                    ColorPicker::make('text_color')
                        ->label(__('Tekstkleur'))
                        ->default(AnnouncementBars::DEFAULT_TEXT)
                        ->rules([$hexRule]),
                    CheckboxList::make('page_types')
                        ->label(__('Alleen tonen op'))
                        ->helperText(__('Niets aangevinkt: op elke pagina.'))
                        ->options(AnnouncementBars::pageTypes())
                        ->columns(2)
                        ->columnSpanFull(),
                ]),
            Section::make(__('Tekst per taal'))
                ->columnSpanFull()
                ->schema($perLocale),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->reorderable('sort')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Naam'))
                    ->description(fn (AnnouncementBar $record) => $record->textFor())
                    ->searchable(),
                IconColumn::make('active')
                    ->label(__('Actief'))
                    ->boolean(),
                TextColumn::make('starts_at')
                    ->label(__('Vanaf'))
                    ->dateTime('d-m-Y H:i')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label(__('Tot'))
                    ->dateTime('d-m-Y H:i')
                    ->placeholder('-')
                    ->sortable(),
                IconColumn::make('countdown')
                    ->label(__('Countdown'))
                    ->boolean(),
            ])
            ->recordActions([
                EditAction::make()->button(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAnnouncementBars::route('/'),
            'create' => CreateAnnouncementBar::route('/create'),
            'edit' => EditAnnouncementBar::route('/{record}/edit'),
        ];
    }
}
