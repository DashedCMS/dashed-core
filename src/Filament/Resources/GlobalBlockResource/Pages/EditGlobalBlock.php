<?php

namespace Dashed\DashedCore\Filament\Resources\GlobalBlockResource\Pages;

use Dashed\DashedCore\Filament\Resources\GlobalBlockResource;
use Filament\Resources\Pages\EditRecord;
use Dashed\DashedPages\Filament\Resources\PageResource;
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
