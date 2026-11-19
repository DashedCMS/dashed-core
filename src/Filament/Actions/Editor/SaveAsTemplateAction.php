<?php

namespace Dashed\DashedCore\Filament\Actions\Editor;

use Filament\Actions\Action;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Classes\Editor\TemplateBlocks;

/**
 * "Opslaan als sjabloon" op een bewerkpagina. Neemt de opgeslagen blokken in
 * de actieve taal, niet de formulierstaat: wat nog niet is opgeslagen kan
 * half ingevuld of ongeldig zijn, en een sjabloon hoort een opgeslagen opzet
 * te zijn. De helptekst zegt dat.
 */
class SaveAsTemplateAction
{
    public static function make(): Action
    {
        return Action::make('saveAsPageTemplate')
            ->label(__('Opslaan als sjabloon'))
            ->icon('heroicon-o-rectangle-stack')
            ->visible(fn ($livewire) => isset($livewire->record)
                && in_array('content', $livewire->record->translatable ?? ['content'], true)
                && (auth()->user()?->can('edit_page_template') ?? false))
            ->schema([
                TextInput::make('name')
                    ->label(__('Naam van het sjabloon'))
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->label(__('Beschrijving'))
                    ->rows(3),
                Toggle::make('only_this_type')
                    ->label(__('Alleen voor dit soort inhoud aanbieden'))
                    ->default(true),
            ])
            ->modalDescription(__('Het sjabloon bevat de opgeslagen blokken in de taal waarin je nu werkt. Sla eerst op als je net iets hebt gewijzigd.'))
            ->action(function (array $data, $livewire) {
                $template = TemplateBlocks::saveFromModel(
                    $livewire->record,
                    $livewire->activeLocale ?? app()->getLocale(),
                    $data['name'],
                    $data['description'] ?? null,
                    (bool) ($data['only_this_type'] ?? true),
                );

                Notification::make()
                    ->title(__('Sjabloon ":naam" is opgeslagen met :aantal blokken', ['naam' => $template->name, 'aantal' => $template->blockCount()]))
                    ->success()
                    ->send();
            });
    }
}
