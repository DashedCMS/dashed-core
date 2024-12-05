<?php

namespace Dashed\DashedCore\Filament\Resources\GlobalBlockResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Dashed\DashedCore\Filament\Resources\GlobalBlockResource;
use Dashed\DashedCore\Filament\Concerns\HasCreatableCMSActions;

class CreateGlobalBlock extends CreateRecord
{
    use HasCreatableCMSActions;

    protected static string $resource = GlobalBlockResource::class;

    protected function getActions(): array
    {
        return self::CMSActions();
    }
}
