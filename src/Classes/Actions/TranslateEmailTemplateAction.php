<?php

namespace Dashed\DashedCore\Classes\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Dashed\DashedCore\Classes\Locales;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Models\EmailTemplate;
use Dashed\DashedTranslations\Classes\AutomatedTranslation;

class TranslateEmailTemplateAction
{
    public static function make(): Action
    {
        return Action::make('translate_email_template')
            ->label(__('Vertaal met DeepL'))
            ->icon('heroicon-o-language')
            ->disabled(fn () => ! AutomatedTranslation::automatedTranslationsEnabled())
            ->tooltip(fn () => ! AutomatedTranslation::automatedTranslationsEnabled()
                ? __('DeepL is niet geconfigureerd')
                : null)
            ->schema([
                Select::make('from_locale')
                    ->label(__('Van locale'))
                    ->options(Locales::getLocalesArray())
                    ->default(fn ($livewire) => $livewire->activeLocale ?? null)
                    ->required(),
                Select::make('to_locales')
                    ->label(__('Naar locales'))
                    ->multiple()
                    ->options(Locales::getLocalesArray())
                    ->default(fn ($livewire) => array_keys(Locales::getLocalesArrayWithoutCurrent($livewire->activeLocale ?? null)))
                    ->required(),
            ])
            ->action(function (array $data, EmailTemplate $record) {
                self::translate($record, $data['from_locale'], $data['to_locales']);

                Notification::make()
                    ->warning()
                    ->title(__('Vertaling gestart'))
                    ->body(__('DeepL-vertaling loopt op de achtergrond; even geduld.'))
                    ->send();
            });
    }

    public static function translate(EmailTemplate $record, string $fromLocale, array $toLocales): void
    {
        AutomatedTranslation::translateModel($record, $fromLocale, $toLocales);
    }
}
