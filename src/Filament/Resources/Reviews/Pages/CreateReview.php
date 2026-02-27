<?php

namespace Dashed\DashedCore\Filament\Resources\Reviews\Pages;

use Filament\Resources\Pages\CreateRecord;
use Dashed\DashedCore\Filament\Resources\Reviews\ReviewResource;

class CreateReview extends CreateRecord
{
    protected static string $resource = ReviewResource::class;
}
