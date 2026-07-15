<?php

namespace Dashed\DashedCore\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Dashed\DashedCore\Models\SentEmail;
use Dashed\DashedCore\Filament\Resources\SentEmailResource\Pages\ListSentEmails;
use Dashed\DashedCore\Filament\Resources\SentEmailResource\Pages\ViewSentEmail;

class SentEmailResource extends Resource
{
    protected static ?string $model = SentEmail::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-envelope';

    protected static string | UnitEnum | null $navigationGroup = 'Mail';

    protected static ?string $navigationLabel = 'Verzonden mails';

    protected static ?string $label = 'Verzonden mail';

    protected static ?string $pluralLabel = 'Verzonden mails';

    protected static bool $isGloballySearchable = false;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Tijd')
                    ->dateTime('d-m-Y H:i')
                    ->sortable(),
                TextColumn::make('to_email')
                    ->label('Ontvanger')
                    ->searchable(),
                TextColumn::make('subject')
                    ->label('Onderwerp')
                    ->searchable()
                    ->limit(50),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        SentEmail::STATUS_QUEUED => 'In wachtrij',
                        SentEmail::STATUS_SENT => 'Verzonden',
                        SentEmail::STATUS_DELIVERED => 'Afgeleverd',
                        SentEmail::STATUS_BOUNCED => 'Gebounced',
                        SentEmail::STATUS_COMPLAINED => 'Spam-klacht',
                        SentEmail::STATUS_FAILED => 'Mislukt',
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSentEmails::route('/'),
            'view' => ViewSentEmail::route('/{record}'),
        ];
    }
}
