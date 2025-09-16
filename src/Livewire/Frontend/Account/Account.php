<?php

namespace Dashed\DashedCore\Livewire\Frontend\Account;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Hash;
use Filament\Notifications\Notification;
use Dashed\DashedCore\Classes\AccountHelper;
use Dashed\DashedTranslations\Models\Translation;

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
        if (auth()->guest()) {
            return redirect(AccountHelper::getLoginUrl());
        }

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
        Notification::make()
            ->title(Translation::get('account-updated-message', 'account', 'Your account has been updated'))
            ->success()
            ->send();
        $this->dispatch('showAlert', 'success', Translation::get('account-updated', 'account', 'Your account has been updated'));
    }

    public function render()
    {
        return view(config('dashed-core.site_theme') . '.account.account');
    }
}
