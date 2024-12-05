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
    }

    public function CMSActions(): array
    {
        return [
            LocaleSwitcher::make(),
        ];
    }
}
