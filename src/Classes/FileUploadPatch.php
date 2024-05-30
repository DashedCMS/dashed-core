<?php

namespace Dashed\DashedCore\Classes;

use Filament\Forms\Components\FileUpload;

class FileUploadPatch extends FileUpload
{
    public function getState(): mixed
    {
        $state = data_get($this->getLivewire(), $this->getStatePath());

        if (is_array($state)) {
            return $state;
        }

        if (blank($state)) {
            return null;
        }

        return [$state];
    }
}
