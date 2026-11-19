<?php

namespace Dashed\DashedCore\Filament\Actions\Editor;

use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Schemas\Components\Text;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Classes\Editor\LinkInserter;
use Dashed\DashedCore\Classes\Editor\LinkSuggestionFinder;

/**
 * "Linksuggesties" op een bewerkpagina: interne links die passen bij de
 * tekst die nu in het formulier staat. De beheerder kiest er één; die komt
 * als link in het formulier en wordt pas bewaard als hij opslaat. Er wordt
 * nooit zonder klik iets in de inhoud gezet.
 */
class LinkSuggestionsAction
{
    public static function make(): Action
    {
        return Action::make('internalLinkSuggestions')
            ->label(__('Linksuggesties'))
            ->icon('heroicon-o-link')
            ->visible(fn ($livewire) => isset($livewire->record) && array_key_exists('content', $livewire->data ?? []))
            ->modalHeading(__('Interne-linksuggesties'))
            ->modalDescription(__('Pagina\'s, producten, categorieën en artikelen waarvan de naam in je tekst voorkomt. Kies er één; de link komt in het formulier en wordt bewaard zodra je opslaat.'))
            ->modalSubmitActionLabel(__('Link toevoegen'))
            ->schema(function ($livewire) {
                $options = static::options($livewire);

                if ($options === []) {
                    return [
                        Text::make(__('Geen suggesties gevonden: er staat geen naam van een andere pagina, product, categorie of artikel los in de tekst.')),
                    ];
                }

                return [
                    Radio::make('suggestion')
                        ->label(__('Suggestie'))
                        ->options(collect($options)->map(fn ($s) => __('":anker" naar :titel', ['anker' => $s['anchor'], 'titel' => $s['title']]))->all())
                        ->descriptions(collect($options)->map(fn ($s) => $s['type'].' · '.$s['url'])->all())
                        ->required(),
                ];
            })
            ->action(function (array $data, $livewire) {
                $suggestion = static::options($livewire)[$data['suggestion'] ?? -1] ?? null;
                if (! $suggestion) {
                    return;
                }

                $new = LinkInserter::insert($livewire->data['content'] ?? [], $suggestion['anchor'], $suggestion['url']);

                if ($new === null) {
                    Notification::make()
                        ->title(__('De tekst ":anker" staat niet meer los in de inhoud', ['anker' => $suggestion['anchor']]))
                        ->warning()
                        ->send();

                    return;
                }

                $livewire->data['content'] = $new;

                Notification::make()
                    ->title(__('Link naar :titel toegevoegd. Sla op om hem te bewaren.', ['titel' => $suggestion['title']]))
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array<int, array{anchor: string, url: string, title: string, type: string, score: int}>
     */
    public static function options($livewire): array
    {
        return LinkSuggestionFinder::find(
            $livewire->record ?? null,
            is_array($livewire->data['content'] ?? null) ? $livewire->data['content'] : [],
            $livewire->activeLocale ?? app()->getLocale(),
        );
    }
}
