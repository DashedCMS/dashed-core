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
use Filament\Tables\Filters\TrashedFilter;
use Filament\Actions\ForceDeleteBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Dashed\DashedCore\Filament\Resources\NotFoundPageResource\Pages\ListNotFoundPage;
use Dashed\DashedCore\Filament\Resources\NotFoundPageResource\Pages\ViewNotFoundPage;

class NotFoundPageResource extends Resource
{
    protected static ?string $model = NotFoundPage::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-no-symbol';

    protected static string | UnitEnum | null $navigationGroup = 'Routes';

    protected static ?string $navigationLabel = 'Niet gevonden pagina hits';

    protected static ?string $label = 'Niet gevonden pagina hit';

    protected static ?string $pluralLabel = 'Niet gevonden pagina hits';
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informatie')->columnSpanFull()
                    ->schema([
                        Forms\Components\TextInput::make('link')
                            ->label('Link'),
                        Forms\Components\TextInput::make('last_occurrence')
                            ->label('Laatst voorgekomen op'),
                        Forms\Components\TextInput::make('total_occurrences')
                            ->label('Totaal aantal keer voorgekomen'),
                        Forms\Components\TextInput::make('site')
                            ->label('Site'),
                        Forms\Components\TextInput::make('locale')
                            ->label('Taal'),
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
        return $table
            ->columns([
                TextColumn::make('link')
                    ->url(fn ($record) => url($record->link))
                    ->openUrlInNewTab()
                    ->label('Link')
                    ->limit(30)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_occurrence')
                    ->dateTime()
                    ->label('Laatst voorgekomen op')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('total_occurrences')
                    ->label('Aantal keer voorgekomen')
                    ->sortable(),
                IconColumn::make('hasRedirect')
                    ->label('Heeft een redirect')
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle'),
            ])
            ->filters([
                TrashedFilter::make(),
                // ...
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make()
                    ->button(),
                \Filament\Actions\Action::make('createRedirect')
                    ->label('Maak redirect aan')
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
                            ->label('Type redirect')
                            ->default('301')
                            ->options([
                                '301' => 'Permanente redirect',
                                '302' => 'Tijdelijke redirect',
                            ]),
                        Forms\Components\DatePicker::make('delete_redirect_after')
                            ->label('Verwijder redirect na een datum')
                            ->default(now()->addMonths(3))
                            ->reactive()
                            ->hintAction(
                                Action::make('emptyDate')
                                    ->label('Leeg datum')
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
                            ->title('Redirect aangemaakt')
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
            //
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
