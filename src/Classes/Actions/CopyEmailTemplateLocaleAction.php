<?php

namespace Dashed\DashedCore\Classes\Actions;

use Filament\Actions\Action;
use InvalidArgumentException;
use Filament\Forms\Components\Select;
use Dashed\DashedCore\Classes\Locales;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Models\EmailTemplate;

class CopyEmailTemplateLocaleAction
{
    public static function make(): Action
    {
        return Action::make('copy_locale')
            ->label(__('Kopieer naar locale'))
            ->icon('heroicon-o-document-duplicate')
            ->modalHeading(__('Kopieer vertalingen van de ene locale naar een andere'))
            ->schema([
                Select::make('from_locale')
                    ->label(__('Van locale'))
                    ->options(Locales::getLocalesArray())
                    ->default(fn ($livewire) => self::defaultFromLocale($livewire))
                    ->required(),
                Select::make('to_locales')
                    ->label(__('Naar locales'))
                    ->multiple()
                    ->options(Locales::getLocalesArray())
                    ->default(function ($livewire) {
                        $from = self::defaultFromLocale($livewire);

                        return collect(Locales::getLocalesArray())
                            ->keys()
                            ->reject(fn ($locale) => $locale === $from)
                            ->values()
                            ->all();
                    })
                    ->required(),
            ])
            ->action(function (array $data, EmailTemplate $record) {
                try {
                    self::copy($record, $data['from_locale'], $data['to_locales']);
                } catch (InvalidArgumentException $e) {
                    Notification::make()->danger()->title(__('Kopiëren mislukt'))->body($e->getMessage())->send();

                    return;
                }

                Notification::make()
                    ->success()
                    ->title(__('Gekopieerd'))
                    ->body(__('Vertalingen gekopieerd naar :talen', ['talen' => implode(', ', $data['to_locales'])]))
                    ->send();
            });
    }

    protected static function defaultFromLocale($livewire): ?string
    {
        $record = (is_object($livewire) && method_exists($livewire, 'getRecord')) ? $livewire->getRecord() : null;

        if ($record instanceof EmailTemplate) {
            return $record->getFallbackLocale() ?? ($livewire->activeLocale ?? null);
        }

        return $livewire->activeLocale ?? null;
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
