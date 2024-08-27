<?php

namespace Dashed\DashedCore\Filament\Resources\RedirectResource\Pages;

use Dashed\DashedCore\Filament\Resources\RedirectResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRedirect extends EditRecord
{
    protected static string $resource = RedirectResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
