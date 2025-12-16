<?php

namespace Dashed\DashedCore\Livewire\Frontend\Protection;

use Exception;
use Livewire\Component;
use Illuminate\Support\Facades\Crypt;
use Dashed\DashedTranslations\Models\Translation;

class PasswordProtection extends Component
{
    public $model;
    public ?string $password = '';

    public function mount()
    {
        try {
            $data = Crypt::decrypt(request()->get('data'));
        } catch (Exception $exception) {
            abort(404);
        }


        if (! isset($data['model']) || ! isset($data['modelId'])) {
            abort(404);
        }

        $model = $data['model']::find($data['modelId']);

        if (! $model) {
            abort(404);
        }

        $this->model = $model;

        if (! $this->model->metadata->password) {
            abort(404);
        }

        if (cms()->hasAccessToModel($this->model)) {
            return redirect($this->model->getUrl());
        }
    }

    public function checkPassword()
    {
        if (! $this->password) {
            $this->addError('password', Translation::get('enter-password', 'password-protection', 'Vul een wachtwoord in'));

            return;
        }

        if ($this->password != $this->model->metadata->password) {
            $this->addError('password', Translation::get('wrong-password', 'password-protection', 'Wachtwoord is onjuist'));

            return;
        }

        $key = sprintf('%s_%d_password', $this->model::class, $this->model->id);

        session()->put($key, $this->model->metadata->password);

        return redirect($this->model->getUrl());
    }

    public function render()
    {
        return view(config('dashed-core.site_theme', 'dashed') . '.protection.password-protection');
    }
}
