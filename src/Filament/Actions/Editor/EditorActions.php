<?php

namespace Dashed\DashedCore\Filament\Actions\Editor;

use Filament\Actions\Action;

/**
 * De editor-acties op elke bewerkpagina met HasEditableCMSActions, op één
 * plek zodat CMSActions() er maar twee regels voor nodig heeft. Elke actie
 * bepaalt zelf of hij zichtbaar is.
 */
class EditorActions
{
    /** @return array<int, Action> naast de knop Bekijk */
    public static function header(): array
    {
        return [
            PreviewAction::make(),
        ];
    }

    /** @return array<int, Action> in de groep Acties */
    public static function grouped(): array
    {
        return [
            LinkSuggestionsAction::make(),
            SaveAsTemplateAction::make(),
        ];
    }
}
