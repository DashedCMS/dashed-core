<?php

namespace Dashed\DashedCore\Filament\Concerns;

use Filament\Actions\Action;
use Dashed\DashedCore\Models\Concerns\IsVisitable;
use Dashed\DashedCore\Filament\Actions\NestableSortingAction;

trait HasNestableSortingAction
{
    protected function getNestableSortingHeaderAction(): ?Action
    {
        $resource = static::getResource();
        $modelClass = $resource::getModel();

        $traits = class_uses_recursive($modelClass);
        if (! in_array(IsVisitable::class, $traits, true)) {
            return null;
        }
        if (! $modelClass::canHaveParent()) {
            return null;
        }

        $query = $modelClass::query();
        if (method_exists($modelClass, 'scopeThisSite')) {
            $query->thisSite();
        }

        return NestableSortingAction::make(
            query: $query,
            parentColumn: $this->getNestableSortingParentColumn(),
            labelColumn: $this->getNestableSortingLabelColumn(),
            orderColumn: $this->getNestableSortingOrderColumn(),
        );
    }

    protected function getNestableSortingParentColumn(): string
    {
        return 'parent_id';
    }

    protected function getNestableSortingLabelColumn(): string
    {
        return 'name';
    }

    protected function getNestableSortingOrderColumn(): string
    {
        return 'order';
    }
}
