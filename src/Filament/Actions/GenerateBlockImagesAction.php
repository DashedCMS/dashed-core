<?php

namespace Dashed\DashedCore\Filament\Actions;

use Filament\Actions\Action;
use Dashed\DashedAi\Facades\Ai;
use Filament\Notifications\Notification;
use Dashed\DashedFiles\Models\AiImageOperation;
use Dashed\DashedFiles\Services\AiImageOperations;
use Dashed\DashedCore\Classes\ContentStudio\ContentStudioImagePatcher;
use Dashed\DashedCore\Classes\ContentStudio\ContentStudioImageScanner;

class GenerateBlockImagesAction
{
    public static function make(): Action
    {
        return Action::make('generateBlockImages')
            ->label('Genereer beelden')
            ->icon('heroicon-o-photo')
            ->visible(fn () => class_exists(Ai::class) && Ai::hasProvider())
            ->requiresConfirmation()
            ->modalDescription('Genereer AI-beelden voor de beeld-velden die door de Content Studio zijn voorbereid. Sla de pagina eerst op.')
            ->action(function ($livewire, $record) {
                if (! $record || ! method_exists($record, 'customBlocks') || ! $record->customBlocks) {
                    Notification::make()->title('Sla de pagina eerst op')->warning()->send();

                    return;
                }

                $locale = method_exists($livewire, 'getActiveSchemaLocale')
                    ? $livewire->getActiveSchemaLocale()
                    : app()->getLocale();

                $blocks = $record->customBlocks->getTranslation('blocks', $locale);
                $pending = is_array($blocks) ? (new ContentStudioImageScanner())->pending($blocks) : [];

                if ($pending === []) {
                    Notification::make()->title('Geen openstaande beeld-prompts')->body('Er zijn geen door AI voorbereide beeld-velden om te vullen.')->info()->send();

                    return;
                }

                $service = app(AiImageOperations::class);
                $customBlockId = $record->customBlocks->id;

                foreach ($pending as $item) {
                    $service->dispatchOne(
                        type: AiImageOperation::TYPE_GENERATE,
                        params: ['prompt' => $item['prompt'], 'ratio' => '1:1'],
                        context: [
                            'handler' => ContentStudioImagePatcher::HANDLER,
                            'custom_block_id' => $customBlockId,
                            'locale' => $locale,
                            'block_key' => $item['block_key'],
                            'field' => $item['field'],
                            'prompt' => $item['prompt'],
                        ],
                    );
                }

                Notification::make()
                    ->title(count($pending) . ' beeld(en) in de wachtrij')
                    ->body('De beelden verschijnen in de blokken zodra ze klaar zijn. Herlaad de pagina om ze te zien.')
                    ->success()
                    ->send();
            });
    }
}
