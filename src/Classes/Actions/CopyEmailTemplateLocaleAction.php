<?php

namespace Dashed\DashedCore\Classes\Actions;

use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedCore\Models\EmailTemplate;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use InvalidArgumentException;

class CopyEmailTemplateLocaleAction
{
    public static function make(): Action
    {
        return Action::make('copy_locale')
            ->label('Kopieer naar locale')
            ->icon('heroicon-o-document-duplicate')
            ->modalHeading('Kopieer vertalingen van de ene locale naar een andere')
            ->schema([
                Select::make('from_locale')
                    ->label('Van locale')
                    ->options(Locales::getLocalesArray())
                    ->default(fn ($livewire) => $livewire->activeLocale ?? null)
                    ->required(),
                Select::make('to_locales')
                    ->label('Naar locales')
                    ->multiple()
                    ->options(Locales::getLocalesArray())
                    ->default(fn ($livewire) => array_keys(Locales::getLocalesArrayWithoutCurrent($livewire->activeLocale ?? null)))
                    ->required(),
            ])
            ->action(function (array $data, EmailTemplate $record) {
                try {
                    self::copy($record, $data['from_locale'], $data['to_locales']);
                } catch (InvalidArgumentException $e) {
                    Notification::make()->danger()->title('Kopiëren mislukt')->body($e->getMessage())->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title('Gekopieerd')
                    ->body('Vertalingen gekopieerd naar ' . implode(', ', $data['to_locales']))
                    ->send();
            });
    }

    public static function copy(EmailTemplate $record, string $fromLocale, array $toLocales): void
    {
        if (in_array($fromLocale, $toLocales, true)) {
            throw new InvalidArgumentException('Je kunt niet naar de bronlocale zelf kopiëren.');
        }

        if (! $record->hasLocaleFilled($fromLocale)) {
            throw new InvalidArgumentException("De bronlocale {$fromLocale} heeft geen gevulde inhoud.");
        }

        foreach (['subject', 'from_name', 'blocks'] as $field) {
            $value = $record->getTranslation($field, $fromLocale);
            foreach ($toLocales as $toLocale) {
                $record->setTranslation($field, $toLocale, $value);
            }
        }

        $record->save();
    }
}
