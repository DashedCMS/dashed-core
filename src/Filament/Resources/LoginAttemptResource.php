<?php

namespace Dashed\DashedCore\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Dashed\DashedCore\Models\LoginAttempt;
use Dashed\DashedCore\Filament\Resources\LoginAttemptResource\Pages\ListLoginAttempts;

class LoginAttemptResource extends Resource
{
    protected static ?string $model = LoginAttempt::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-finger-print';

    protected static string | UnitEnum | null $navigationGroup = 'Gebruikers';

    protected static ?int $navigationSort = 120;

    protected static ?string $navigationLabel = 'Inlogpogingen';

    protected static ?string $label = 'Inlogpoging';

    protected static ?string $pluralLabel = 'Inlogpogingen';

    protected static bool $isGloballySearchable = false;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    /**
     * Mislukte pogingen van de laatste 24 uur, zodat een golf opvalt zonder
     * te klikken.
     */
    public static function getNavigationBadge(): ?string
    {
        $count = LoginAttempt::query()
            ->whereIn('result', [LoginAttempt::RESULT_FAILED, LoginAttempt::RESULT_FAILED_MFA])
            ->where('created_at', '>=', now()->subDay())
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('Mislukte inlogpogingen in de laatste 24 uur');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('Tijd'))
                    ->dateTime('d-m-Y H:i:s')
                    ->sortable(),
                TextColumn::make('result')
                    ->label(__('Uitkomst'))
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => LoginAttempt::labels()[$state] ?? $state)
                    ->color(fn (string $state): string => LoginAttempt::colors()[$state] ?? 'gray')
                    ->sortable(),
                TextColumn::make('email')
                    ->label(__('E-mailadres'))
                    ->placeholder('-')
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label(__('Gebruiker'))
                    ->placeholder(__('Onbekend account')),
                TextColumn::make('ip')
                    ->label(__('IP-adres'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('user_agent')
                    ->label(__('Browser'))
                    ->limit(40)
                    ->tooltip(fn (LoginAttempt $record): ?string => $record->user_agent)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('result')
                    ->label(__('Uitkomst'))
                    ->multiple()
                    ->options(LoginAttempt::labels()),
                SelectFilter::make('user_id')
                    ->label(__('Gebruiker'))
                    ->relationship('user', 'email')
                    ->searchable()
                    ->preload(),
                Filter::make('periode')
                    ->schema([
                        DatePicker::make('vanaf')->label(__('Vanaf')),
                        DatePicker::make('tot')->label(__('Tot en met')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['vanaf'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
                        ->when($data['tot'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d))),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLoginAttempts::route('/'),
        ];
    }
}
