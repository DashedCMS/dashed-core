<?php

namespace Dashed\DashedCore\Filament\Resources\GlobalBlockResource\Pages;

use Dashed\DashedCore\Filament\Resources\GlobalBlockResource;
use Filament\Resources\Pages\CreateRecord;
use Dashed\DashedPages\Filament\Resources\PageResource;
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
