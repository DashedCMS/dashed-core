<?php

use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Livewire\Component;

class SEOScoreInfoList extends Component implements HasForms, HasInfolists
{
    use InteractsWithInfolists;
    use InteractsWithForms;


    public function infoList(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->product)
            ->schema([
                // ...
            ]);
    }
}
