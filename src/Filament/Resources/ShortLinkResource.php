<?php

namespace Dashed\DashedCore\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Filament\Actions\DeleteAction;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Select;
use Filament\Actions\BulkActionGroup;
use Dashed\DashedCore\Models\ShortLink;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Filament\Schemas\Components\Section;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components\Placeholder;
use Dashed\DashedCore\Classes\ShortLinks\ShortLinks;
use Dashed\DashedCore\Classes\ShortLinks\ShortLinkQr;
use Dashed\DashedCore\Filament\Resources\ShortLinkResource\Pages\EditShortLink;
use Dashed\DashedCore\Filament\Resources\ShortLinkResource\Pages\ListShortLinks;
use Dashed\DashedCore\Filament\Resources\ShortLinkResource\Pages\CreateShortLink;

/**
 * De UTM-linkbouwer: een campagnelink met korte link /k/{code}, QR-code,
 * kliks per dag en de omzet van bestellingen met dezelfde UTM-velden.
 */
class ShortLinkResource extends Resource
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesResourceLabels;

    protected static ?string $model = ShortLink::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-link';

    protected static string | UnitEnum | null $navigationGroup = 'SEO & site';

    protected static ?string $navigationLabel = 'Campagnelinks';

    protected static ?string $label = 'Campagnelink';

    protected static ?string $pluralLabel = 'Campagnelinks';

    protected static ?int $navigationSort = 31;

    protected static bool $isGloballySearchable = false;

    public static function form(Schema $schema): Schema
    {
        $presets = ShortLinks::presets();

        return $schema->schema([
            Section::make(__('Campagnelink'))
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label(__('Naam'))
                        ->helperText(__('Alleen voor jezelf, bijvoorbeeld "Flyer kerstmarkt".'))
                        ->required()
                        ->maxLength(255),
                    Select::make('site_id')
                        ->label(__('Site'))
                        ->options(collect(Sites::getSites())->pluck('name', 'id')->all())
                        ->default(Sites::getActive())
                        ->required()
                        ->visible(fn () => Sites::getAmountOfSites() > 1),
                    TextInput::make('destination_url')
                        ->label(__('Doel-URL'))
                        ->helperText(__('Een pagina op je eigen site, als pad (/actie) of volledige URL.'))
                        ->required()
                        ->maxLength(2048)
                        ->live(onBlur: true)
                        ->columnSpanFull()
                        ->rules([
                            fn (Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                if (! ShortLinks::isAllowedDestination((string) $value, $get('site_id') ?: Sites::getActive())) {
                                    $fail(__('Een campagnelink mag alleen naar je eigen site wijzen.'));
                                }
                            },
                        ]),
                    Select::make('channel')
                        ->label(__('Kanaal'))
                        ->helperText(__('Vult bron en medium in; je kunt ze daarna nog aanpassen.'))
                        ->options(collect($presets)->map(fn ($preset) => $preset['label'])->all())
                        ->live()
                        ->afterStateUpdated(function (?string $state, Set $set) use ($presets): void {
                            if ($state && isset($presets[$state])) {
                                $set('utm_source', $presets[$state]['utm_source']);
                                $set('utm_medium', $presets[$state]['utm_medium']);
                            }
                        }),
                    TextInput::make('code')
                        ->label(__('Code van de korte link'))
                        ->helperText(__('Leeg laten voor een willekeurige code. Letters, cijfers, - en _.'))
                        ->regex('/^' . ShortLinks::CODE_PATTERN . '$/')
                        ->unique(ignoreRecord: true)
                        ->maxLength(32),
                    TextInput::make('utm_source')
                        ->label(__('Bron (utm_source)'))
                        ->required()
                        ->live(onBlur: true)
                        ->maxLength(255),
                    TextInput::make('utm_medium')
                        ->label(__('Medium (utm_medium)'))
                        ->required()
                        ->live(onBlur: true)
                        ->maxLength(255),
                    TextInput::make('utm_campaign')
                        ->label(__('Campagne (utm_campaign)'))
                        ->required()
                        ->live(onBlur: true)
                        ->maxLength(255),
                    TextInput::make('utm_term')
                        ->label(__('Zoekterm (utm_term)'))
                        ->live(onBlur: true)
                        ->maxLength(255),
                    TextInput::make('utm_content')
                        ->label(__('Inhoud (utm_content)'))
                        ->helperText(__('Om twee links in dezelfde campagne uit elkaar te houden, bijvoorbeeld "flyer-a".'))
                        ->live(onBlur: true)
                        ->maxLength(255),
                    Placeholder::make('preview')
                        ->label(__('Volledige link'))
                        ->columnSpanFull()
                        ->content(function (Get $get): string {
                            $destination = (string) $get('destination_url');

                            if ($destination === '') {
                                return '-';
                            }

                            $utm = [];
                            foreach (ShortLinks::UTM_FIELDS as $field) {
                                $utm[$field] = trim((string) $get($field));
                            }

                            return ShortLinks::buildTargetUrl(ShortLinks::absolute($destination, $get('site_id') ?: Sites::getActive()), $utm);
                        }),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => ShortLinks::withStats($query))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->label(__('Naam'))
                    ->description(fn (ShortLink $record) => $record->utm_campaign)
                    ->searchable(['name', 'utm_campaign', 'code']),
                TextColumn::make('code')
                    ->label(__('Korte link'))
                    ->formatStateUsing(fn (ShortLink $record) => $record->shortUrl())
                    ->copyable()
                    ->copyableState(fn (ShortLink $record) => $record->shortUrl()),
                TextColumn::make('utm_source')
                    ->label(__('Bron / medium'))
                    ->formatStateUsing(fn (ShortLink $record) => $record->utm_source . ' / ' . $record->utm_medium)
                    ->toggleable(),
                TextColumn::make('clicks_30_days')
                    ->label(__('Kliks 30 dagen'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('click_count')
                    ->label(__('Kliks totaal'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('orders_count')
                    ->label(__('Bestellingen'))
                    ->numeric()
                    ->sortable()
                    ->visible(fn () => ShortLinks::ordersHaveAttribution()),
                TextColumn::make('orders_revenue')
                    ->label(__('Omzet'))
                    ->money('EUR')
                    ->sortable()
                    ->visible(fn () => ShortLinks::ordersHaveAttribution()),
                TextColumn::make('last_clicked_at')
                    ->label(__('Laatste klik'))
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('channel')
                    ->label(__('Kanaal'))
                    ->options(collect(ShortLinks::presets())->map(fn ($preset) => $preset['label'])->all()),
            ])
            ->recordActions([
                static::qrAction(),
                EditAction::make()->button(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function qrAction(): Action
    {
        return Action::make('qr')
            ->label(__('QR-code'))
            ->icon('heroicon-o-qr-code')
            ->visible(fn () => ShortLinkQr::available())
            ->modalHeading(fn (ShortLink $record) => __('QR-code voor :naam', ['naam' => $record->name]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('Sluiten'))
            ->modalContent(fn (ShortLink $record) => new HtmlString(
                '<div style="max-width:320px;margin:0 auto">' . (ShortLinkQr::svg($record->shortUrl()) ?? '') . '</div>'
                . '<p style="text-align:center;margin-top:8px;font-size:13px">' . e($record->shortUrl()) . '</p>'
            ))
            ->extraModalFooterActions(fn (ShortLink $record) => [
                Action::make('downloadQr')
                    ->label(__('Download SVG'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => response()->streamDownload(
                        fn () => print(ShortLinkQr::svg($record->shortUrl())),
                        'qr-' . $record->code . '.svg',
                        ['Content-Type' => 'image/svg+xml'],
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShortLinks::route('/'),
            'create' => CreateShortLink::route('/create'),
            'edit' => EditShortLink::route('/{record}/edit'),
        ];
    }
}
