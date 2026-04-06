<?php

namespace Dashed\DashedCore\Filament\Actions;

use Filament\Actions\BulkAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;
use Dashed\DashedCore\Classes\ClaudeHelper;
use Dashed\DashedCore\Jobs\AnalyzeSeoJob;
use Dashed\DashedCore\Models\SeoImprovement;

class BulkAnalyzeSeoAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'bulk-analyze-seo';
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

        $this->action(function (Collection $records, array $data): void {
            $locale = app()->getLocale();
            $count = 0;

            foreach ($records as $record) {
                $improvement = SeoImprovement::updateOrCreate(
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

                AnalyzeSeoJob::dispatch($improvement, $locale, $data['instruction'] ?? '');
                $count++;
            }

            Notification::make()
                ->title("{$count} SEO analyse(s) gestart")
                ->body('De analyses worden op de achtergrond uitgevoerd. Bekijk de resultaten bij SEO Verbetervoorstellen.')
                ->success()
                ->send();
        });
    }
}
