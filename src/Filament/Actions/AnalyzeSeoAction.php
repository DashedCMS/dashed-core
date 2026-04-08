<?php

namespace Dashed\DashedCore\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Jobs\AnalyzeSeoJob;
use Dashed\DashedCore\Classes\ClaudeHelper;
use Dashed\DashedCore\Models\SeoImprovement;

class AnalyzeSeoAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'analyze-seo';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label('SEO analyseren');
        $this->icon('heroicon-o-magnifying-glass');
        $this->color('info');

        $this->visible(fn () => ClaudeHelper::isConnected());

        $this->schema([
            Textarea::make('instruction')
                ->label('Extra instructie (optioneel)')
                ->placeholder('Bijv: Focus op de FAQ sectie, of: herschrijf de meta description volledig')
                ->rows(2),
        ]);

        $this->action(function (array $data, $livewire): void {
            $record = $livewire->record;

            $voorstel = SeoImprovement::updateOrCreate(
                [
                    'subject_type' => $record->getMorphClass(),
                    'subject_id' => $record->getKey(),
                ],
                [
                    'status' => 'analyzing',
                    'created_by' => auth()->id(),
                    'keyword_research' => null,
                    'analysis_summary' => null,
                    'field_proposals' => null,
                    'block_proposals' => null,
                    'error_message' => null,
                    'applied_at' => null,
                    'applied_by' => null,
                ],
            );

            AnalyzeSeoJob::dispatch(
                $voorstel,
                $livewire->activeLocale ?? app()->getLocale(),
                $data['instruction'] ?? '',
            );

            Notification::make()
                ->title('SEO analyse gestart')
                ->body('De analyse wordt op de achtergrond uitgevoerd. Bekijk de resultaten bij SEO Verbetervoorstellen.')
                ->success()
                ->send();
        });
    }
}
