<?php

namespace Dashed\DashedCore\Filament\Actions\Editor;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Models\PageTemplate;
use Dashed\DashedCore\Classes\Editor\TemplateBlocks;

/**
 * "Sjabloon laden" op een aanmaakpagina: zet de blokken van het sjabloon in
 * het formulier, in plaats van wat er stond. Er wordt niets opgeslagen; dat
 * doet de beheerder zelf met Aanmaken.
 */
class LoadTemplateAction
{
    public static function make(): Action
    {
        return Action::make('loadPageTemplate')
            ->label(__('Sjabloon laden'))
            ->icon('heroicon-o-rectangle-stack')
            ->color('gray')
            ->visible(fn ($livewire) => TemplateBlocks::options($livewire::getResource()::getModel()) !== [])
            ->schema(fn ($livewire) => [
                Select::make('template')
                    ->label(__('Sjabloon'))
                    ->options(TemplateBlocks::options($livewire::getResource()::getModel()))
                    ->helperText(__('De blokken in het formulier worden vervangen door die van het sjabloon.'))
                    ->required()
                    ->searchable(),
            ])
            ->action(function (array $data, $livewire) {
                $template = PageTemplate::query()->forModel($livewire::getResource()::getModel())->findOrFail($data['template']);

                $livewire->form->fillPartially(['content' => TemplateBlocks::sanitize($template->content)], ['content']);

                Notification::make()
                    ->title(__('Sjabloon ":naam" is geladen', ['naam' => $template->name]))
                    ->success()
                    ->send();
            });
    }
}
