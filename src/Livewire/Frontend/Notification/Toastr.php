<?php

namespace Dashed\DashedCore\Livewire\Frontend\Notification;

use Livewire\Component;

class Toastr extends Component
{
    public $successMessage;
    public $errorMessage;

    protected $listeners = [
        'showAlert',
    ];

    public function showAlert(string $type, string $message)
    {
        $this->dispatchBrowserEvent(
            'alert',
            [
                'type' => $type,
                'message' => $message,
            ]
        );
    }

    public function render()
    {
        return view('dashed-core::frontend.notification.toastr');
    }
}
