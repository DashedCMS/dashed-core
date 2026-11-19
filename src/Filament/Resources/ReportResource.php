<?php

namespace Dashed\DashedCore\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Dashed\DashedCore\Models\Report;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Dashed\DashedCore\Reports\ReportPeriod;
use Filament\Forms\Components\CheckboxList;
use Dashed\DashedCore\Reports\ReportExporter;
use Dashed\DashedCore\Reports\ReportRegistry;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Dashed\DashedCore\Filament\Resources\ReportResource\Pages\EditReport;
use Dashed\DashedCore\Filament\Resources\ReportResource\Pages\ViewReport;
use Dashed\DashedCore\Filament\Resources\ReportResource\Pages\ListReports;
use Dashed\DashedCore\Filament\Resources\ReportResource\Pages\CreateReport;

/**
 * De rapportbouwer: een beheerder kiest een bron, meetwaarden, een
 * groepering, filters en een periode, en ziet het resultaat als tabel en
 * grafiek. Alle keuzes komen uit de bron in het register (ReportRegistry);
 * er is geen veld waarin SQL of een kolomnaam getypt kan worden.
 */
class ReportResource extends Resource
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesResourceLabels;

    protected static ?string $model = Report::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static string | UnitEnum | null $navigationGroup = 'Statistieken';

    protected static ?int $navigationSort = 900;

    protected static ?string $navigationLabel = 'Rapporten';

    protected static ?string $label = 'Rapport';

    protected static ?string $pluralLabel = 'Rapporten';

    protected static bool $isGloballySearchable = false;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Rapport'))
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label(__('Naam'))
                        ->required()
                        ->maxLength(255),
                    Select::make('source')
                        ->label(__('Bron'))
                        ->options(fn () => ReportRegistry::options(auth()->user()))
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (Set $set) {
                            $set('measures', []);
                            $set('grouping', null);
                            $set('filters', []);
                        }),
                    CheckboxList::make('measures')
                        ->label(__('Meetwaarden'))
                        ->options(fn (Get $get) => static::measureOptions($get('source')))
                        ->required()
                        ->columns(2)
                        ->visible(fn (Get $get) => filled($get('source'))),
                    Select::make('grouping')
                        ->label(__('Groeperen op'))
                        ->placeholder(__('Niet groeperen, alleen het totaal'))
                        ->options(fn (Get $get) => static::groupingOptions($get('source')))
                        ->visible(fn (Get $get) => filled($get('source'))),
                    Select::make('period')
                        ->label(__('Periode'))
                        ->options(ReportPeriod::options())
                        ->default('last_30_days')
                        ->required()
                        ->live(),
                    Select::make('chart')
                        ->label(__('Grafiek'))
                        ->options(['bar' => __('Staafdiagram'), 'line' => __('Lijn'), 'none' => __('Geen grafiek')])
                        ->default('bar')
                        ->required(),
                    DatePicker::make('date_from')
                        ->label(__('Van'))
                        ->visible(fn (Get $get) => $get('period') === 'custom')
                        ->required(fn (Get $get) => $get('period') === 'custom'),
                    DatePicker::make('date_to')
                        ->label(__('Tot en met'))
                        ->visible(fn (Get $get) => $get('period') === 'custom')
                        ->required(fn (Get $get) => $get('period') === 'custom'),
                ]),
            Section::make(__('Filters'))
                ->columnSpanFull()
                ->columns(2)
                ->visible(fn (Get $get) => static::filterFields($get('source')) !== [])
                ->schema(fn (Get $get) => static::filterFields($get('source'))),
            Section::make(__('Inplannen'))
                ->description(__('Het rapport gaat als mail met bijlage naar de ontvangers. Dagelijks elke ochtend, wekelijks op maandag, maandelijks op de eerste van de maand. De periode wordt op het moment van versturen uitgerekend.'))
                ->columnSpanFull()
                ->columns(2)
                ->schema([
                    Select::make('schedule')
                        ->label(__('Versturen'))
                        ->options([
                            'none' => __('Niet inplannen'),
                            'daily' => __('Dagelijks'),
                            'weekly' => __('Wekelijks'),
                            'monthly' => __('Maandelijks'),
                        ])
                        ->default('none')
                        ->required()
                        ->live(),
                    Select::make('schedule_format')
                        ->label(__('Bestandsformaat'))
                        ->options(ReportExporter::formats())
                        ->default(ReportExporter::xlsxAvailable() ? 'xlsx' : 'csv')
                        ->required(),
                    TagsInput::make('recipients')
                        ->label(__('Ontvangers'))
                        ->placeholder(__('E-mailadres toevoegen'))
                        ->nestedRecursiveRules(['email'])
                        ->required(fn (Get $get) => $get('schedule') !== 'none')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    /** @return array<string, string> */
    public static function measureOptions(?string $source): array
    {
        $source = ReportRegistry::get($source);

        return $source ? array_map(fn ($measure) => $measure->label, $source->measures()) : [];
    }

    /** @return array<string, string> */
    public static function groupingOptions(?string $source): array
    {
        $source = ReportRegistry::get($source);

        return $source ? array_map(fn ($grouping) => $grouping->label, $source->groupings()) : [];
    }

    /** @return array<int, Select> */
    public static function filterFields(?string $source): array
    {
        $source = ReportRegistry::get($source);
        if (! $source) {
            return [];
        }

        $fields = [];
        foreach ($source->filters() as $key => $filter) {
            $fields[] = Select::make('filters.'.$key)
                ->label($filter->label)
                ->multiple()
                ->searchable()
                ->options(fn () => ($filter->options)());
        }

        return $fields;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Naam'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('source')
                    ->label(__('Bron'))
                    ->formatStateUsing(fn ($state) => ReportRegistry::get($state)?->label() ?? $state),
                TextColumn::make('period')
                    ->label(__('Periode'))
                    ->formatStateUsing(fn ($state) => ReportPeriod::options()[$state] ?? $state),
                TextColumn::make('schedule')
                    ->label(__('Ingepland'))
                    ->formatStateUsing(fn ($state) => [
                        'none' => __('Nee'),
                        'daily' => __('Dagelijks'),
                        'weekly' => __('Wekelijks'),
                        'monthly' => __('Maandelijks'),
                    ][$state] ?? $state),
                TextColumn::make('last_sent_at')
                    ->label(__('Laatst verstuurd'))
                    ->dateTime('d-m-Y H:i')
                    ->placeholder('-'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReports::route('/'),
            'create' => CreateReport::route('/create'),
            'view' => ViewReport::route('/{record}'),
            'edit' => EditReport::route('/{record}/edit'),
        ];
    }
}
