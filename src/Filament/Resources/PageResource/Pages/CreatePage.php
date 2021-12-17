<?php

namespace Qubiqx\QcommerceCore\Filament\Resources\PageResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Qubiqx\QcommerceCore\Filament\Resources\PageResource;

class CreatePage extends CreateRecord
{
    use CreateRecord\Concerns\Translatable;

    protected static string $resource = PageResource::class;
}
