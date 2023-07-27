<?php

namespace Qubiqx\QcommerceCore\Livewire\Frontend\Account;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Qubiqx\QcommerceTranslations\Models\Translation;

class Account extends Component
{
    public User $user;

    public ?string $email = '';
    public ?string $firstName = '';
    public ?string $lastName = '';
    public ?string $password = '';
    public ?string $passwordConfirmation = '';

    public function mount()
    {
        $this->user = auth()->user();
        $this->email = $this->user->email;
        $this->firstName = $this->user->first_name;
        $this->lastName = $this->user->last_name;
    }

    public function rules()
    {
        return [
            'firstName' => [
                'max:255',
            ],
            'lastName' => [
                'max:255',
            ],
            'password' => [
                'nullable',
                'min:6',
                'max:255',
                'confirmed',
            ],
            'password' => [
                'nullable',
                'min:6',
                'max:255',
                'required',
            ],
            'passwordConfirmation' => [
                'min:6',
                'max:255',
                'required_with:password',
                'same:password',
            ],
        ];
    }

    public function submit()
    {
        $this->validate();

        $this->user->first_name = $this->firstName;
        $this->user->last_name = $this->lastName;

        if ($this->password) {
            $this->user->password = Hash::make($this->password);
        }

        $this->user->save();
        $this->reset(['password', 'passwordConfirmation']);
        $this->emit('showAlert', 'success', Translation::get('account-updated', 'account', 'Your account has been updated'));
    }

    public function render()
    {
        return view('qcommerce-core::frontend.account.account');
    }
}
