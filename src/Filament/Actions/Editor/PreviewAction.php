<?php

namespace Dashed\DashedCore\Filament\Actions\Editor;

use Filament\Actions\Action;
use Filament\Support\Enums\Width;
use Dashed\DashedCore\Classes\Editor\EditorPreview;

/**
 * "Voorvertoning" op een bewerkpagina: de onopgeslagen formulierstaat in het
 * thema van de site, in een iframe met een schakelaar telefoon, tablet en
 * desktop. Zie EditorPreview voor de opslag en de route.
 */
class PreviewAction
{
    public static function make(): Action
    {
        return Action::make('editorPreview')
            ->label(__('Voorvertoning'))
            ->icon('heroicon-o-device-phone-mobile')
            ->color('gray')
            ->visible(fn ($livewire) => EditorPreview::supports($livewire->record ?? null))
            ->modalHeading(__('Voorvertoning'))
            ->modalDescription(__('Zo ziet de pagina eruit met wat nu in het formulier staat, ook als het nog niet is opgeslagen.'))
            ->modalWidth(Width::Full)
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(__('Sluiten'))
            ->modalContent(fn ($livewire) => view('dashed-core::editor.preview-modal', [
                'url' => static::urlFor($livewire),
            ]));
    }

    public static function urlFor($livewire): string
    {
        $state = rescue(fn () => $livewire->form->getRawState(), $livewire->data ?? [], report: false);

        $token = EditorPreview::store(
            $livewire->record,
            is_array($state) ? $state : [],
            $livewire->activeLocale ?? app()->getLocale(),
        );

        return EditorPreview::url($token);
    }
}
