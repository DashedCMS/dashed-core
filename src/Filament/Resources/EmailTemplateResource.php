<?php

namespace Dashed\DashedCore\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Builder;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Dashed\DashedCore\Models\EmailTemplate;
use Dashed\DashedCore\Mail\EmailBlocks\EmailBlock;
use Dashed\DashedCore\Filament\Resources\EmailTemplateResource\Pages\EditEmailTemplate;
use Dashed\DashedCore\Filament\Resources\EmailTemplateResource\Pages\ListEmailTemplates;

class EmailTemplateResource extends Resource
{
    protected static ?string $model = EmailTemplate::class;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-envelope';

    protected static string | UnitEnum | null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'E-mail templates';

    protected static ?string $label = 'E-mail template';

    protected static ?string $pluralLabel = 'E-mail templates';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Algemeen')
                ->schema([
                    TextInput::make('name')
                        ->label('Naam')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('mailable_key')
                        ->label('Mailable class')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('subject')
                        ->label('Onderwerp')
                        ->helperText(fn ($record) => self::variableHint($record))
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('from_name')->label('Afzender naam'),
                    TextInput::make('from_email')->label('Afzender e-mail')->email(),
                    Toggle::make('is_active')->label('Actief')->columnSpanFull(),
                ])
                ->columns(2)
                ->columnSpanFull(),

            Section::make('Inhoud')
                ->schema([
                    Builder::make('blocks')
                        ->label('Blokken')
                        ->blocks(fn ($record) => self::allowedBlocksFor($record))
                        ->collapsible()
                        ->cloneable()
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ]);
    }

    protected static function variableHint($record): string
    {
        if (! $record) {
            return '';
        }
        $mailable = cms()->emailTemplateRegistry()->find($record->mailable_key);
        if (! $mailable) {
            return '';
        }
        $vars = $mailable::availableVariables();

        return $vars ? 'Beschikbaar: ' . collect($vars)->map(fn ($v) => '{{ ' . $v . ' }}')->join(', ') : '';
    }

    /** @return array<int, \Filament\Forms\Components\Builder\Block> */
    protected static function allowedBlocksFor($record): array
    {
        /** @var array<string, class-string<EmailBlock>> $registry */
        $registry = cms()->emailBlocks();

        $mailable = $record ? cms()->emailTemplateRegistry()->find($record->mailable_key) : null;

        if (! $mailable) {
            return collect($registry)->map(fn ($class) => $class::filamentBlock())->values()->all();
        }

        $allowed = $mailable::availableBlockKeys();

        return collect($registry)
            ->filter(fn ($class, $key) => in_array($key, $allowed, true))
            ->map(fn ($class) => $class::filamentBlock())
            ->values()
            ->all();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Naam')->searchable()->sortable(),
                TextColumn::make('mailable_key')->label('Mailable')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('subject')->label('Onderwerp')->limit(40),
                IconColumn::make('is_active')->boolean()->label('Actief'),
                TextColumn::make('updated_at')->label('Bijgewerkt')->dateTime('d-m-Y H:i')->sortable(),
            ])
            ->recordActions([
                EditAction::make()->button(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailTemplates::route('/'),
            'edit' => EditEmailTemplate::route('/{record}/edit'),
        ];
    }
}
