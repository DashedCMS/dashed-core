<?php

namespace Dashed\DashedCore\Filament\Resources;

use UnitEnum;
use BackedEnum;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Builder;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Placeholder;
use Dashed\DashedCore\Models\EmailTemplate;
use Dashed\DashedCore\Mail\EmailBlocks\EmailBlock;
use LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable;
use Dashed\DashedCore\Filament\Resources\EmailTemplateResource\Pages\EditEmailTemplate;
use Dashed\DashedCore\Filament\Resources\EmailTemplateResource\Pages\ListEmailTemplates;

class EmailTemplateResource extends Resource
{
    use Translatable;

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
            Placeholder::make('locale_warning')
                ->hiddenLabel()
                ->visible(fn ($record) => $record && self::localeWarningText($record) !== null)
                ->content(fn ($record) => new HtmlString(
                    '<div class="text-sm text-warning-600">' . e(self::localeWarningText($record)) . '</div>'
                ))
                ->columnSpanFull(),

            Section::make('Beschikbare variabelen')
                ->description('Gebruik deze variabelen in onderwerp en tekstblokken. Ze worden vervangen door de echte waarde bij verzenden.')
                ->schema([
                    Placeholder::make('available_variables')
                        ->hiddenLabel()
                        ->content(fn ($record) => self::availableVariablesList($record))
                        ->columnSpanFull(),
                ])
                ->visible(fn ($record) => $record && cms()->emailTemplateRegistry()->find($record->mailable_key))
                ->columnSpanFull(),

            Section::make('Algemeen')
                ->schema([
                    Placeholder::make('name')
                        ->label('Naam')
                        ->content(fn ($record) => $record?->name ?? '-'),
                    Placeholder::make('mailable_key')
                        ->label('Mailable class')
                        ->content(fn ($record) => $record?->mailable_key ?? '-'),
                    TextInput::make('subject')
                        ->label('Onderwerp')
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('from_name')
                        ->label('Afzender naam')
                        ->placeholder(fn () => \Dashed\DashedCore\Models\Customsetting::get('site_name'))
                        ->helperText('Laat leeg om de standaard afzendernaam uit de site instellingen te gebruiken.'),
                    TextInput::make('from_email')
                        ->label('Afzender e-mail')
                        ->email()
                        ->placeholder(fn () => \Dashed\DashedCore\Models\Customsetting::get('site_from_email'))
                        ->helperText('Laat leeg om het standaard afzenderadres uit de site instellingen te gebruiken.'),
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

    protected static function availableVariablesList($record): HtmlString
    {
        if (! $record) {
            return new HtmlString('');
        }
        $mailable = cms()->emailTemplateRegistry()->find($record->mailable_key);
        if (! $mailable) {
            return new HtmlString('');
        }
        $vars = $mailable::availableVariables();
        if (empty($vars)) {
            return new HtmlString('<em>Geen variabelen beschikbaar voor deze mailable.</em>');
        }

        $text = collect($vars)
            ->map(fn ($v) => ':' . $v . ':')
            ->join(', ');

        return new HtmlString(e($text));
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
                TextColumn::make('locale_status')
                    ->label('Locales')
                    ->state(fn ($record) => self::localeStatusLabel($record))
                    ->badge()
                    ->color(fn ($record) => empty($record->missingLocales()) ? 'success' : 'warning')
                    ->tooltip(fn ($record) => empty($record->missingLocales())
                        ? null
                        : 'Ontbrekend: ' . implode(', ', array_map('strtoupper', $record->missingLocales()))),
                IconColumn::make('is_active')->boolean()->label('Actief'),
                TextColumn::make('updated_at')->label('Bijgewerkt')->dateTime('d-m-Y H:i')->sortable(),
            ])
            ->recordActions([
                EditAction::make()->button(),
            ]);
    }

    public static function localeStatusLabel(EmailTemplate $template): string
    {
        $total = count(\Dashed\DashedCore\Classes\Locales::getLocales());
        $filled = $total - count($template->missingLocales());

        return "{$filled} / {$total}";
    }

    public static function localeWarningText(EmailTemplate $template): ?string
    {
        $missing = $template->missingLocales();
        if (empty($missing)) {
            return null;
        }

        $fallback = $template->getFallbackLocale();

        return sprintf(
            'Let op: de locales %s zijn nog niet volledig ingevuld. Verzending naar klanten in die talen valt terug op %s.',
            implode(', ', array_map('strtoupper', $missing)),
            strtoupper((string) $fallback)
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEmailTemplates::route('/'),
            'edit' => EditEmailTemplate::route('/{record}/edit'),
        ];
    }

    public static function getTranslatableLocales(): array
    {
        return collect(\Dashed\DashedCore\Classes\Locales::getLocales())
            ->pluck('id')
            ->all();
    }
}
