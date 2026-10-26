<?php

namespace Dashed\DashedCore\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Classes\Trash\Trash;
use Dashed\DashedCore\Classes\Trash\TrashUndo;

class TrashUndoController
{
    public function __invoke(string $token): RedirectResponse
    {
        $restored = TrashUndo::undo($token, auth()->id());

        if ($restored === []) {
            Notification::make()->title(__('Er viel niets meer terug te zetten.'))->warning()->send();

            return redirect()->back();
        }

        Notification::make()
            ->title(trans_choice(':aantal item teruggezet|:aantal items teruggezet', count($restored), ['aantal' => count($restored)]))
            ->success()
            ->send();

        if (count($restored) === 1 && ($url = Trash::editUrl($restored[0]))) {
            return redirect()->to($url);
        }

        return redirect()->back();
    }
}
