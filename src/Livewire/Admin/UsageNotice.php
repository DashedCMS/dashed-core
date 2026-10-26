<?php

namespace Dashed\DashedCore\Livewire\Admin;

use Livewire\Component;
use Livewire\Attributes\Lazy;
use Livewire\Attributes\Locked;
use Dashed\DashedCore\Classes\Usage\UsageFinder;

/**
 * "Wordt gebruikt op 3 plekken" bovenaan een bewerkpagina. Lui geladen: het
 * zoeken door alle blokken hoort niet in de request van de pagina zelf.
 */
#[Lazy]
class UsageNotice extends Component
{
    #[Locked]
    public string $modelClass = '';

    #[Locked]
    public string $recordKey = '';

    public function placeholder(): string
    {
        return '<div></div>';
    }

    public function render()
    {
        $usages = collect();

        if (class_exists($this->modelClass)) {
            $record = $this->modelClass::query()->find($this->recordKey);
            $usages = $record ? UsageFinder::find($record) : collect();
        }

        return view('dashed-core::livewire.admin.usage-notice', ['usages' => $usages]);
    }
}
