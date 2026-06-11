<?php

namespace Dashed\DashedCore\Services\VisualEditor;

use Throwable;
use Filament\Facades\Filament;

class VisualEditor
{
    public const SESSION_FLAG = 'dashed_visual_editor';

    public function isAdmin(): bool
    {
        if (! auth()->check()) {
            return false;
        }

        try {
            $panel = Filament::getDefaultPanel();

            return (bool) auth()->user()?->canAccessPanel($panel);
        } catch (Throwable) {
            return false;
        }
    }

    public function isActive(): bool
    {
        return $this->isAdmin() && (bool) session(self::SESSION_FLAG, false);
    }
}
