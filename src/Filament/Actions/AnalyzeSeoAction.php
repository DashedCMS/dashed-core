<?php

namespace Dashed\DashedCore\Filament\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Classes\ClaudeHelper;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Jobs\AnalyzeSeoJob;
use Dashed\DashedCore\Models\SeoVerbetervoorstel;
use Dashed\DashedCore\Filament\Resources\SeoVerbetervoorstelResource;

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

        $this->visible(fn ($livewire) => ClaudeHelper::isConnected()
            && method_exists($livewire->record, 'metadata'));

        $this->schema([
            Textarea::make('instruction')
                ->label('Extra instructie (optioneel)')
                ->placeholder('Bijv: Focus op de FAQ sectie, of: herschrijf de meta description volledig')
                ->rows(2),
        ]);

        $this->action(function (array $data, $livewire): void {
            $record = $livewire->record;

            $voorstel = SeoVerbetervoorstel::create([
                'subject_type' => $record->getMorphClass(),
                'subject_id' => $record->getKey(),
                'status' => 'analyzing',
                'created_by' => auth()->id(),
            ]);

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
