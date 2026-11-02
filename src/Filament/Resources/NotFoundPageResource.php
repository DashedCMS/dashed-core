<?php

namespace Dashed\DashedCore\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Forms;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\BulkActionGroup;
use Dashed\DashedCore\Models\Redirect;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Dashed\DashedCore\Classes\UrlHelper;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use Dashed\DashedCore\Models\NotFoundPage;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ForceDeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Dashed\DashedCore\Filament\Resources\NotFoundPageResource\Pages\ListNotFoundPage;
use Dashed\DashedCore\Filament\Resources\NotFoundPageResource\Pages\ViewNotFoundPage;
use Dashed\DashedCore\Filament\Resources\NotFoundPageResource\RelationManagers\OccurrencesRelationManager;

class NotFoundPageResource extends Resource
{
    use \Dashed\DashedCore\Filament\Concerns\TranslatesResourceLabels;

    protected static ?string $model = NotFoundPage::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-no-symbol';

    protected static string | UnitEnum | null $navigationGroup = 'SEO & site';

    protected static ?string $navigationLabel = 'Niet gevonden pagina hits';

    protected static ?string $label = 'Niet gevonden pagina hit';

    protected static ?string $pluralLabel = 'Niet gevonden pagina hits';
    protected static ?int $navigationSort = 40;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('Informatie'))->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('link')
                            ->label(__('Link')),
                        Forms\Components\TextInput::make('last_occurrence')
                            ->label(__('Laatst voorgekomen op')),
                        Forms\Components\TextInput::make('total_occurrences')
                            ->label(__('Totaal aantal keer voorgekomen')),
                        Forms\Components\TextInput::make('site')
                            ->label(__('Site')),
                        Forms\Components\TextInput::make('locale')
                            ->label(__('Taal')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function table(Table $table): Table
    {
        $host = request()->getHost();

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('latestOccurrence'))
            ->columns([
                TextColumn::make('link')
                    ->url(fn ($record) => url($record->link))
                    ->openUrlInNewTab()
                    ->label(__('Link'))
                    ->limit(30)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_occurrence')
                    ->dateTime()
                    ->label(__('Laatst voorgekomen op'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_occurrences')
                    ->label(__('Aantal keer voorgekomen'))
                    ->sortable(),
                TextColumn::make('latestOccurrence.referer')
                    ->label(__('Laatste verwijzer'))
                    ->placeholder(__('Geen verwijzer'))
                    ->url(fn ($record) => $record->latestOccurrence?->referer ?: null)
                    ->openUrlInNewTab()
                    ->limit(40)
                    ->tooltip(fn ($record) => strlen((string) $record->latestOccurrence?->referer) > 40 ? $record->latestOccurrence->referer : null)
                    ->badge(fn ($record) => (bool) $record->latestOccurrence?->referer)
                    ->color(fn ($record) => str_contains((string) $record->latestOccurrence?->referer, '://' . $host) ? 'warning' : 'gray')
                    ->searchable(),
                IconColumn::make('hasRedirect')
                    ->label(__('Heeft een redirect'))
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
            ])
            ->filters([
                TrashedFilter::make(),
                TernaryFilter::make('own_site_referer')
                    ->label(__('Verwijzer op eigen site'))
                    ->placeholder(__('Alle pagina\'s'))
                    ->trueLabel(__('Met een hit vanaf eigen site'))
                    ->falseLabel(__('Alleen hits van buiten of zonder verwijzer'))
                    ->queries(
                        true: fn (Builder $query) => $query->whereHas('occurrences', fn (Builder $q) => $q->referredFromOwnSite($host)),
                        false: fn (Builder $query) => $query->whereDoesntHave('occurrences', fn (Builder $q) => $q->referredFromOwnSite($host)),
                    ),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make()
                    ->button(),
                \Filament\Actions\Action::make('createRedirect')
                    ->label(__('Maak redirect aan'))
                    ->button()
                    ->schema([
                        linkHelper()->field(required: true),
                        //                        Forms\Components\TextInput::make('to')
                        //                            ->required()
                        //                            ->label('Naar welke URL moet deze redirect verwijzen?')
                        //                            ->reactive()
                        //                            ->helperText(fn(Forms\Get $get) => !$get('to') ? 'Vul een URL in' : (UrlHelper::checkUrlResponseCode(url($get('to'))) == 200 ? 'Deze URL is bereikbaar' : 'Deze URL is niet bereikbaar')),
                        Forms\Components\Select::make('sort')
                            ->required()
                            ->label(__('Type redirect'))
                            ->default('301')
                            ->options([
                                '301' => __('Permanente redirect'),
                                '302' => __('Tijdelijke redirect'),
                            ]),
                        Forms\Components\DatePicker::make('delete_redirect_after')
                            ->label(__('Verwijder redirect na een datum'))
                            ->default(now()->addMonths(3))
                            ->reactive()
                            ->hintAction(
                                Action::make('emptyDate')
                                    ->label(__('Leeg datum'))
                                    ->icon('heroicon-o-clock')
                                    ->action(function (Forms\Set $set) {
                                        $set('delete_redirect_after', null);
                                    })
                            ),
                    ])
                    ->action(function ($record, array $data) {
                        $redirect = Redirect::create([
                            'from' => $record->link,
                            'to' => str(linkHelper()->getUrl($data))->replace(url('/'), ''),
                            'sort' => $data['sort'],
                            'delete_redirect_after' => $data['delete_redirect_after'],
                        ]);

                        Notification::make()
                            ->title(__('Redirect aangemaakt'))
                            ->success()
                            ->send();
                    }),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            OccurrencesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNotFoundPage::route('/'),
            'view' => ViewNotFoundPage::route('/{record}/edit'),
        ];
    }
}
