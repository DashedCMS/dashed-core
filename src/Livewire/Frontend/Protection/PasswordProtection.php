<?php

namespace Dashed\DashedCore\Livewire\Frontend\Protection;

use Livewire\Component;
use Illuminate\Support\Facades\Crypt;

class PasswordProtection extends Component
{
    public $model;

    public function mount()
    {
        $data = Crypt::decrypt(request()->get('data'));

        if (! isset($data['model']) || ! isset($data['modelId'])) {
            abort(404);
        }
    }

    public function render()
    {
        return view(env('SITE_THEME', 'dashed') . '.protection.password-protection');
    }
}
