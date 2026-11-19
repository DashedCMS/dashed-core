<?php

namespace Dashed\DashedCore\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Dashed\DashedCore\Models\PageTemplate;
use Dashed\DashedCore\Filament\Resources\PageTemplateResource\Pages\EditPageTemplate;
use Dashed\DashedCore\Filament\Resources\PageTemplateResource\Pages\ListPageTemplates;
use Dashed\DashedCore\Filament\Resources\PageTemplateResource\Pages\CreatePageTemplate;

/**
 * Paginasjablonen beheren. Meestal ontstaat een sjabloon via "Opslaan als
 * sjabloon" op een pagina; hier pas je naam, beschrijving en blokken aan.
 */
class PageTemplateResource extends Resource
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesResourceLabels;

    protected static ?string $model = PageTemplate::class;

    protected static ?string $recordTitleAttribute = 'name';
    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static string | UnitEnum | null $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Paginasjablonen';
    protected static ?string $label = 'Paginasjabloon';
    protected static ?string $pluralLabel = 'Paginasjablonen';
    protected static ?int $navigationSort = 2;

    /**
     * @return array<string, string>
     */
    public static function modelTypeOptions(): array
    {
        return collect(cms()->builder('routeModels'))
            ->filter(fn ($routeModel) => isset($routeModel['class']) && class_exists($routeModel['class']))
            ->mapWithKeys(fn ($routeModel) => [$routeModel['class'] => (string) $routeModel['name']])
            ->all();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('Sjabloon'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Naam'))
                            ->required()
                            ->maxLength(255),
                        Select::make('model_type')
                            ->label(__('Beschikbaar voor'))
                            ->placeholder(__('Alle soorten inhoud'))
                            ->options(static::modelTypeOptions()),
                        Textarea::make('description')
                            ->label(__('Beschrijving'))
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
                Section::make(__('Blokken'))
                    ->schema([
                        cms()->getFilamentBuilderBlock(globalBlockChooser: false),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('Naam'))
                    ->description(fn (PageTemplate $record) => $record->description)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('model_type')
                    ->label(__('Beschikbaar voor'))
                    ->formatStateUsing(fn (?string $state) => static::modelTypeOptions()[$state] ?? $state)
                    ->placeholder(__('Alle soorten inhoud')),
                TextColumn::make('blocks')
                    ->label(__('Blokken'))
                    ->state(fn (PageTemplate $record) => $record->blockCount()),
                TextColumn::make('updated_at')
                    ->label(__('Bijgewerkt'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('name')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->recordActions([
                EditAction::make()
                    ->button(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPageTemplates::route('/'),
            'create' => CreatePageTemplate::route('/create'),
            'edit' => EditPageTemplate::route('/{record}/edit'),
        ];
    }
}
