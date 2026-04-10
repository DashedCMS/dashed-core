<?php

namespace Dashed\DashedCore\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Dashed\DashedCore\Models\Export;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Storage;
use Filament\Tables\Filters\SelectFilter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Dashed\DashedCore\Filament\Resources\ExportResource\Pages\ListExports;

class ExportResource extends Resource
{
    protected static ?string $model = Export::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-document-arrow-down';

    protected static string | UnitEnum | null $navigationGroup = 'Export';

    protected static ?string $navigationLabel = 'Export overzicht';

    protected static ?string $label = 'Export';

    protected static ?string $pluralLabel = 'Exports';

    protected static ?int $navigationSort = 0;

    protected static bool $isGloballySearchable = false;

    public static function canAccess(): bool
    {
        return auth()->check() && auth()->user()->can('view_exports');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('label')
                    ->label('Export')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Gebruiker')
                    ->sortable(),
                TextColumn::make('parameters')
                    ->label('Parameters')
                    ->state(function (Export $record) {
                        $parameters = $record->parameters;

                        if (! is_array($parameters) || empty($parameters)) {
                            return '-';
                        }

                        $parts = [];
                        foreach ($parameters as $key => $value) {
                            if ($value === null || $value === '') {
                                continue;
                            }
                            if (is_array($value)) {
                                $value = implode(', ', $value);
                            }
                            if (is_bool($value)) {
                                $value = $value ? 'ja' : 'nee';
                            }
                            $parts[] = static::humanizeKey($key) . ': ' . $value;
                        }

                        return $parts ? implode(' · ', $parts) : '-';
                    })
                    ->wrap(),
                TextColumn::make('file_size')
                    ->label('Grootte')
                    ->formatStateUsing(fn ($state) => $state ? static::formatBytes((int) $state) : '-')
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
                TextColumn::make('completed_at')
                    ->label('Voltooid op')
                    ->dateTime('d-m-Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Type')
                    ->options(fn () => Export::query()->distinct()->pluck('type', 'type')->all()),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        Export::STATUS_QUEUED => 'In wachtrij',
                        Export::STATUS_PROCESSING => 'Bezig...',
                        Export::STATUS_COMPLETED => 'Voltooid',
                        Export::STATUS_FAILED => 'Mislukt',
                    ]),
            ])
            ->recordActions([
                Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('primary')
                    ->button()
                    ->visible(fn (Export $record) => $record->status === Export::STATUS_COMPLETED && $record->fileExists())
                    ->action(function (Export $record): StreamedResponse {
                        return Storage::disk($record->disk)->download(
                            $record->file_path,
                            $record->file_name,
                        );
                    }),
                Action::make('viewError')
                    ->label('Bekijk fout')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->button()
                    ->visible(fn (Export $record) => $record->status === Export::STATUS_FAILED && $record->error_message)
                    ->modalHeading('Foutmelding')
                    ->modalContent(fn (Export $record) => view('dashed-core::exports.error-modal', ['message' => $record->error_message]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Sluiten'),
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
            'index' => ListExports::route('/'),
        ];
    }

    protected static function humanizeKey(string $key): string
    {
        // camelCase -> spaces, snake_case -> spaces, then ucfirst
        $withSpaces = preg_replace('/(?<!^)[A-Z]/', ' $0', $key);
        $withSpaces = str_replace(['_', '-'], ' ', $withSpaces);
        $withSpaces = trim(preg_replace('/\s+/', ' ', $withSpaces));

        return ucfirst(strtolower($withSpaces));
    }

    protected static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
