<?php

namespace Dashed\DashedCore\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Dashed\DashedCore\Models\AbTest;
use Dashed\DashedCore\Classes\Sites;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Builder;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Placeholder;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Dashed\DashedCore\Classes\AbTests\AbTests;
use Dashed\DashedCore\Classes\AbTests\AbTestApplier;
use Dashed\DashedCore\Filament\Resources\AbTestResource\Pages\EditAbTest;
use Dashed\DashedCore\Filament\Resources\AbTestResource\Pages\ListAbTests;

/**
 * A/B-tests op blokken. Een test maak je vanuit de pagina-builder (de knop
 * A/B-test op een blok); hier stel je variant B, de verdeling en het doel in
 * en lees je de uitkomst.
 */
class AbTestResource extends Resource
{
    protected static ?string $model = AbTest::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static string|UnitEnum|null $navigationGroup = 'Content marketing';

    protected static ?string $slug = 'ab-tests';

    protected static ?int $navigationSort = 90;

    public static function getModelLabel(): string
    {
        return __('A/B-test');
    }

    public static function getPluralModelLabel(): string
    {
        return __('A/B-tests');
    }

    public static function getNavigationLabel(): string
    {
        return __('A/B-tests');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return AbTests::enabled() && parent::shouldRegisterNavigation();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): EloquentBuilder
    {
        return parent::getEloquentQuery()->where('site_id', Sites::getActive());
    }

    /** Het blok uit de builder-registry met deze naam, of null. */
    public static function blockDefinition(string $type): ?Builder\Block
    {
        cms()->activateBuilderBlockClasses();

        foreach (cms()->builder('blocks') as $block) {
            if ($block instanceof Builder\Block && $block->getName() === $type) {
                return $block;
            }
        }

        return null;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('Test'))
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label(__('Naam'))
                        ->required()
                        ->maxLength(150),
                    Select::make('goal')
                        ->label(__('Doel'))
                        ->options(AbTest::goalOptions())
                        ->required()
                        ->selectablePlaceholder(false)
                        ->helperText(__('Een bestelling of formulier telt als het in dezelfde browser gebeurt, binnen 30 dagen na het zien van de variant.')),
                    TextInput::make('split_b')
                        ->label(__('Aandeel variant B (%)'))
                        ->numeric()
                        ->integer()
                        ->minValue(1)
                        ->maxValue(99)
                        ->required()
                        ->helperText(__('Wijzig dit niet terwijl de test loopt: bezoekers die al een variant hebben gezien houden die.')),
                    Placeholder::make('page')
                        ->label(__('Pagina'))
                        ->content(fn (?AbTest $record) => static::pageLink($record)),
                    Placeholder::make('status_label')
                        ->label(__('Status'))
                        ->content(fn (?AbTest $record) => $record ? (AbTest::statusOptions()[$record->status] ?? $record->status) . ($record->winner ? ' (' . __('winnaar :variant', ['variant' => strtoupper($record->winner)]) . ')' : '') : '-'),
                    Placeholder::make('block_found')
                        ->label(__('Blok op de pagina'))
                        ->content(fn (?AbTest $record) => $record && AbTestApplier::locate($record)
                            ? __('Gevonden')
                            : __('Niet gevonden: sla de pagina op na het maken van de test, of het blok is verwijderd.')),
                ]),
            Section::make(__('Resultaten'))
                ->schema([
                    Placeholder::make('statistics')
                        ->hiddenLabel()
                        ->content(fn (?AbTest $record) => $record
                            ? new HtmlString(view('dashed-core::ab-tests.statistics', ['test' => $record, 'stats' => AbTests::statistics($record)])->render())
                            : null),
                ]),
            Section::make(__('Variant B'))
                ->description(__('Variant A is het blok zoals het op de pagina staat.'))
                ->schema(fn (?AbTest $record) => [
                    ($record && ($block = static::blockDefinition($record->block_type)))
                        ? Builder::make('variant_b')
                            ->hiddenLabel()
                            ->blocks([$block])
                            ->minItems(1)
                            ->maxItems(1)
                            ->addable(false)
                            ->deletable(false)
                            ->reorderable(false)
                            ->cloneable(false)
                            ->collapsible(false)
                        : Placeholder::make('variant_b_missing')
                            ->hiddenLabel()
                            ->content(__('Dit bloktype bestaat niet meer in dit project.')),
                ]),
        ]);
    }

    public static function pageLink(?AbTest $record): HtmlString|string
    {
        $model = $record?->model;
        if (! $model) {
            return '-';
        }

        $name = method_exists($model, 'getTranslation') && $model->isTranslatableAttribute('name')
            ? $model->getTranslation('name', $record->locale ?: app()->getLocale())
            : ($model->name ?? class_basename($model) . ' #' . $model->getKey());
        $url = method_exists($model, 'getUrl') ? rescue(fn () => $model->getUrl($record->locale), null, false) : null;

        return $url
            ? new HtmlString('<a href="' . e($url) . '" target="_blank" rel="noopener" class="underline">' . e($name) . '</a>')
            : (string) $name;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (EloquentBuilder $query) => $query
                ->with('model')
                ->withSum(['stats as views_a' => fn ($q) => $q->where('variant', 'a')], 'views')
                ->withSum(['stats as views_b' => fn ($q) => $q->where('variant', 'b')], 'views'))
            ->description(__('Een test maak je in de pagina-builder met de knop A/B-test op een blok.'))
            ->columns([
                TextColumn::make('name')->label(__('Naam'))->searchable(),
                TextColumn::make('page')->label(__('Pagina'))
                    ->state(fn (AbTest $record) => static::pageLink($record)),
                TextColumn::make('block_type')->label(__('Blok')),
                TextColumn::make('goal')->label(__('Doel'))
                    ->formatStateUsing(fn (string $state) => AbTest::goalOptions()[$state] ?? $state),
                TextColumn::make('status')->label(__('Status'))->badge()
                    ->formatStateUsing(fn (string $state) => AbTest::statusOptions()[$state] ?? $state)
                    ->color(fn (string $state) => match ($state) {
                        AbTest::STATUS_RUNNING => 'success',
                        AbTest::STATUS_FINISHED => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('views_a')->label(__('Vertoningen A'))->numeric()->placeholder('0'),
                TextColumn::make('views_b')->label(__('Vertoningen B'))->numeric()->placeholder('0'),
                TextColumn::make('started_at')->label(__('Gestart'))->dateTime('d-m-Y')->placeholder('-')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordActions([
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAbTests::route('/'),
            'edit' => EditAbTest::route('/{record}/edit'),
        ];
    }
}
