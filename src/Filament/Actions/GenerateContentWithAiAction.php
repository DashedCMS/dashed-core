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
            ->label('Genereer met AI')
            ->icon('heroicon-o-sparkles')
            ->visible(fn () => Ai::hasProvider())
            ->schema([
                Textarea::make('brief')
                    ->label('Beschrijf de pagina')
                    ->placeholder("Bijv. landingspagina voor de zomeractie met USP's en 3 reviews")
                    ->required()
                    ->rows(4),
                Radio::make('mode')
                    ->label('Toepassen')
                    ->options([
                        'append' => 'Toevoegen aan bestaande blokken',
                        'replace' => 'Bestaande blokken vervangen',
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
                        ->title('Geen bruikbaar resultaat')
                        ->body('De AI gaf geen geldige blokken terug. Pas de beschrijving aan en probeer opnieuw.')
                        ->warning()
                        ->send();

                    return;
                }

                $existing = $data['mode'] === 'replace'
                    ? []
                    : array_values((array) ($get('customBlocks') ?? []));

                $set('customBlocks', array_merge($existing, $blocks));

                Notification::make()
                    ->title(count($blocks) . ' blok(ken) gegenereerd')
                    ->body('Controleer en pas ze aan in de editor, en sla daarna op.')
                    ->success()
                    ->send();
            });
    }
}
