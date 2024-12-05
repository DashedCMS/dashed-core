<?php

namespace Dashed\DashedCore\Filament\Resources\GlobalBlockResource\Pages;

use Filament\Resources\Pages\EditRecord;
use Dashed\DashedCore\Filament\Resources\GlobalBlockResource;
use Dashed\DashedCore\Filament\Concerns\HasEditableCMSActions;

class EditGlobalBlock extends EditRecord
{
    use HasEditableCMSActions;

    protected static string $resource = GlobalBlockResource::class;

    protected function getActions(): array
    {
        return self::CMSActions();
    }
}
