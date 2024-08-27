<?php

namespace Dashed\DashedCore\Filament\Resources\UserResource\Users;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Dashed\DashedCore\Filament\Resources\UserResource;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
