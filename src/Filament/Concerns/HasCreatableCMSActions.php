<?php

namespace Dashed\DashedCore\Filament\Concerns;

use Filament\Actions\LocaleSwitcher;
use Filament\Resources\Pages\CreateRecord\Concerns\Translatable;

trait HasCreatableCMSActions
{
    use Translatable;

    public function updatingActiveLocale($newVal): void
    {
        $this->oldActiveLocale = $this->activeLocale;

        //        if (method_exists($this->getRecord(), 'customBlocks')) {
        //            $this->data['customBlocks'] = $this->getRecord()->customBlocks->getTranslation('blocks', $newVal);
        //        }
        //
        //        if (method_exists($this->getRecord(), 'metadata')) {
        //            foreach ($this->getRecord()->metadata->getTranslatableAttributes() as $attribute) {
        //                $this->data['metadata'][$attribute] = $this->getRecord()->metadata->getTranslation($attribute, $newVal);
        //            }
        //        }
    }

    public function CMSActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }
}
