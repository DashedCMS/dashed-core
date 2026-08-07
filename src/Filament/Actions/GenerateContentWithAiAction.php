<?php

namespace Dashed\DashedCore\Filament\Actions;

use Filament\Actions\Action;
use Dashed\DashedAi\Facades\Ai;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Classes\ContentStudio\BlockCatalog;
use Dashed\DashedCore\Classes\ContentStudio\ContentStudioGenerator;

class GenerateContentWithAiAction
{
    public static function make(string $blocksName): Action
    {
        return Action::make('generateContentWithAi')
            ->label(__('Genereer met AI'))
            ->icon('heroicon-o-sparkles')
            ->visible(fn () => class_exists(Ai::class) && Ai::hasProvider())
            ->schema([
                Textarea::make('brief')
                    ->label(__('Beschrijf de pagina'))
                    ->placeholder(__("Bijv. landingspagina voor de zomeractie met USP's en 3 reviews"))
                    ->required()
                    ->rows(4),
                Radio::make('mode')
                    ->label(__('Toepassen'))
                    ->options([
                        'append' => __('Toevoegen aan bestaande blokken'),
                        'replace' => __('Bestaande blokken vervangen'),
                    ])
                    ->default('append')
                    ->required(),
            ])
            ->action(function (array $data, $livewire, callable $get, callable $set) use ($blocksName) {
                $locale = method_exists($livewire, 'getActiveSchemaLocale')
                    ? $livewire->getActiveSchemaLocale()
                    : app()->getLocale();

                $catalog = (new BlockCatalog())->for($blocksName);

                $blocks = (new ContentStudioGenerator())->generate($data['brief'], $catalog, $locale);

                if ($blocks === []) {
                    Notification::make()
                        ->title(__('Geen bruikbaar resultaat'))
                        ->body(__('De AI gaf geen geldige blokken terug. Pas de beschrijving aan en probeer opnieuw.'))
                        ->warning()
                        ->send();

                    return;
                }

                $existing = $data['mode'] === 'replace'
                    ? []
                    : array_values((array) ($get('customBlocks') ?? []));

                $set('customBlocks', array_merge($existing, $blocks));

                Notification::make()
                    ->title(__(':aantal blok(ken) gegenereerd', ['aantal' => count($blocks)]))
                    ->body(__('Controleer en pas ze aan in de editor, en sla daarna op.'))
                    ->success()
                    ->send();
            });
    }
}
