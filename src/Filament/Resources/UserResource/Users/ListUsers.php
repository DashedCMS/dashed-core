<?php

namespace Qubiqx\QcommerceCore\Filament\Resources\UserResource\Users;

use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Pages\ListRecords\Concerns\Translatable;
use Qubiqx\QcommerceCore\Filament\Resources\UserResource;
use Qubiqx\QcommercePages\Filament\Resources\PageResource;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;
}
