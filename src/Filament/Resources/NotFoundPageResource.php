<?php

namespace Dashed\DashedCore\Filament\Resources;

use Dashed\DashedCore\Classes\LinkHelper;
use Dashed\DashedCore\Classes\UrlHelper;
use Dashed\DashedCore\Filament\Resources\NotFoundPageResource\Pages\ListNotFoundPage;
use Dashed\DashedCore\Filament\Resources\NotFoundPageResource\Pages\ViewNotFoundPage;
use Dashed\DashedCore\Models\NotFoundPage;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Dashed\DashedCore\Models\Redirect;
use Filament\Forms\Components\Section;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class NotFoundPageResource extends Resource
{
    protected static ?string $model = NotFoundPage::class;

    protected static ?string $navigationIcon = 'heroicon-o-no-symbol';
    protected static ?string $navigationGroup = 'Routes';
    protected static ?string $navigationLabel = 'Niet gevonden pagina hits';
    protected static ?string $label = 'Niet gevonden pagina hit';
    protected static ?string $pluralLabel = 'Niet gevonden pagina hits';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Informatie')
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
                    ->columns(2)
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
                    ->url(fn($record) => url($record->link))
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
            ])
            ->filters([
                TrashedFilter::make(),
                // ...
            ])
            ->actions([
                \Filament\Tables\Actions\ViewAction::make()
                    ->button(),
                \Filament\Tables\Actions\Action::make('createRedirect')
                    ->label('Maak redirect aan')
                    ->button()
                    ->form([
                        LinkHelper::field(required: true),
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
                            ->default(now()->addMonths(3)),
                    ])
                    ->action(function ($record, array $data) {
                        $redirect = Redirect::create([
                            'from' => $record->link,
                            'to' => str(LinkHelper::getUrl($data))->replace(url('/'), ''),
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
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);;
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
