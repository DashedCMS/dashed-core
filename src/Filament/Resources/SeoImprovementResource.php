<?php

namespace Dashed\DashedCore\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedCore\Jobs\AnalyzeSeoJob;
use Dashed\DashedCore\Models\SeoImprovement;
use Dashed\DashedCore\Filament\Resources\SeoImprovementResource\Pages\ListSeoImprovements;
use Dashed\DashedCore\Filament\Resources\SeoImprovementResource\Pages\ViewSeoImprovement;

class SeoImprovementResource extends Resource
{
    protected static ?string $model = SeoImprovement::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-sparkles';

    protected static string | UnitEnum | null $navigationGroup = 'SEO';

    protected static ?string $navigationLabel = 'SEO Verbetervoorstellen';

    protected static ?string $label = 'SEO Verbetervoorstel';

    protected static ?string $pluralLabel = 'SEO Verbetervoorstellen';

    protected static ?int $navigationSort = 10;

    protected static bool $isGloballySearchable = false;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('subject_type')
                    ->label('Type')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->sortable(),
                TextColumn::make('subject_id')
                    ->label('Record ID')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn ($record) => $record->status_color)
                    ->formatStateUsing(fn ($record) => $record->status_label)
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Aangemaakt op')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
                TextColumn::make('applied_at')
                    ->label('Toegepast op')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'analyzing' => 'Bezig met analyseren',
                        'ready' => 'Klaar voor review',
                        'applied' => 'Toegepast',
                        'failed' => 'Mislukt',
                    ]),
                SelectFilter::make('subject_type')
                    ->label('Type')
                    ->options(function () {
                        return SeoImprovement::query()
                            ->distinct()
                            ->pluck('subject_type')
                            ->mapWithKeys(fn ($type) => [$type => class_basename($type)])
                            ->toArray();
                    }),
                Filter::make('not_applied')
                    ->label('Nog niet toegepast')
                    ->query(fn (Builder $query) => $query->whereNull('applied_at'))
                    ->default(),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make()->button(),
                \Filament\Actions\Action::make('retry')
                    ->label('Opnieuw')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn ($record) => in_array($record->status, ['failed', 'analyzing']))
                    ->action(function ($record): void {
                        $record->update([
                            'status' => 'analyzing',
                            'error_message' => null,
                            'keyword_research' => null,
                            'analysis_summary' => null,
                            'field_proposals' => null,
                            'block_proposals' => null,
                        ]);

                        AnalyzeSeoJob::dispatch(
                            $record,
                            Locales::getFirstLocale()['id'] ?? app()->getLocale(),
                        );

                        Notification::make()
                            ->title('Analyse opnieuw gestart')
                            ->success()
                            ->send();
                    }),
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
            'index' => ListSeoImprovements::route('/'),
            'view' => ViewSeoImprovement::route('/{record}'),
        ];
    }
}
