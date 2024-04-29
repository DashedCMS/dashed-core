<?php

namespace Dashed\DashedCore\Filament\Concerns;

use Dashed\DashedCore\Classes\Locales;
use Dashed\DashedCore\Filament\Actions\ShowSEOScoreAction;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\LocaleSwitcher;
use Illuminate\Support\Str;

trait HasEditableCMSActions
{
    public function CMSActions(): array
    {
        return [
            Action::make('view')
                ->button()
                ->label('Bekijk')
                ->url($this->record->getUrl())
                ->openUrlInNewTab(),
            Action::make('Dupliceer')
                ->action('duplicate')
                ->color('warning'),
            ShowSEOScoreAction::make(),
            LocaleSwitcher::make(),
            DeleteAction::make(),
        ];
    }

    public function duplicate()
    {
        $newModel = $this->record->replicate();
        foreach (Locales::getLocales() as $locale) {
            $newModel->setTranslation('slug', $locale['id'], $newModel->getTranslation('slug', $locale['id']));
            while ($this->record::class::where('slug->' . $locale['id'], $newModel->getTranslation('slug', $locale['id']))->count()) {
                $newModel->setTranslation('slug', $locale['id'], $newModel->getTranslation('slug', $locale['id']) . Str::random(1));
            }
        }

        $newModel->save();

        if ($this->record->customBlocks) {
            $newCustomBlock = $this->record->customBlocks->replicate();
            $newCustomBlock->blockable_id = $newModel->id;
            $newCustomBlock->save();
        }

        if ($this->record->metaData) {
            $newMetaData = $this->record->metaData->replicate();
            $newMetaData->metadatable_id = $newModel->id;
            $newMetaData->save();
        }

        return redirect(self::getUrl(['record' => $newModel]));
    }
}
