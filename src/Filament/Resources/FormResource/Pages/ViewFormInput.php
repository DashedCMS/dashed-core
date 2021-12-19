<?php

namespace Qubiqx\QcommerceCore\Filament\Resources\FormResource\Pages;

use Filament\Resources\Pages\Page;
use Qubiqx\QcommerceCore\Filament\Resources\FormResource;
use Qubiqx\QcommerceCore\Models\FormInput;

class ViewFormInput extends Page
{
    protected static string $resource = FormResource::class;

    protected static string $view = 'qcommerce-core::forms.pages.view-form-input';

    public $record;

    public function mount($record, FormInput $formInput): void
    {
        if ($formInput->form->id != $record) {
            abort(404);
        }

        $this->record = $formInput;
    }

    protected function getBreadcrumbs(): array
    {
        $breadcrumbs = parent::getBreadcrumbs();
        $lastBreadcrumb = $breadcrumbs[0];
        array_pop($breadcrumbs);
        $breadcrumbs[route('filament.resources.forms.view', [$this->record->form->id])] = "Aanvragen voor {$this->record->form->name}";
        $breadcrumbs[] = $lastBreadcrumb;
        return $breadcrumbs;
    }

    protected function getTitle(): string
    {
        return "Aanvraag #{$this->record->id} voor {$this->record->form->name}";
    }
}
