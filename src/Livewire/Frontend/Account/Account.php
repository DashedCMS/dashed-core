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
    public ?User $user = null;

    public ?string $email = '';

    public ?string $firstName = '';

    public ?string $lastName = '';

    public ?string $password = '';

    public ?string $passwordConfirmation = '';
    public ?string $phoneNumber = '';
    public ?string $street = '';
    public ?string $houseNr = '';
    public ?string $zipCode = '';
    public ?string $city = '';
    public ?string $country = '';

    public bool $isCompany = false;
    public ?string $company = '';
    public ?string $taxId = '';

    public ?string $invoiceStreet = '';
    public ?string $invoiceHouseNr = '';
    public ?string $invoiceZipCode = '';
    public ?string $invoiceCity = '';
    public ?string $invoiceCountry = '';

    public ?string $dateOfBirth = '';
    public ?string $gender = '';

    public function mount()
    {
        if (auth()->guest()) {
            return redirect(AccountHelper::getLoginUrl());
        }

        $this->user = auth()->user();
        $this->email = $this->user->email;
        $this->firstName = $this->user->first_name;
        $this->lastName = $this->user->last_name;
        $this->phoneNumber = $this->user->phone_number;
        $this->street = $this->user->street;
        $this->houseNr = $this->user->house_nr;
        $this->zipCode = $this->user->zip_code;
        $this->city = $this->user->city;
        $this->country = $this->user->country;

        $this->isCompany = $this->user->is_company;
        $this->company = $this->user->company;
        $this->taxId = $this->user->tax_id;

        $this->invoiceStreet = $this->user->invoice_street;
        $this->invoiceHouseNr = $this->user->invoice_house_nr;
        $this->invoiceZipCode = $this->user->invoice_zip_code;
        $this->invoiceCity = $this->user->invoice_city;
        $this->invoiceCountry = $this->user->invoice_country;

        $this->dateOfBirth = $this->user->date_of_birth;
        $this->gender = $this->user->gender;
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

        // Basic
        $this->user->first_name = $this->firstName;
        $this->user->last_name = $this->lastName;

        $this->user->phone_number = $this->phoneNumber ?: null;
        $this->user->date_of_birth = $this->dateOfBirth ?: null;
        $this->user->gender = $this->gender ?: null;
        $this->user->street = $this->street ?: null;
        $this->user->house_nr = $this->houseNr ?: null;
        $this->user->zip_code = $this->zipCode ?: null;
        $this->user->city = $this->city ?: null;
        $this->user->country = $this->country ?: null;
        $this->user->is_company = (bool)$this->isCompany;
        $this->user->company = ($this->isCompany ?? false) ? ($this->company ?: null) : null;
        $this->user->tax_id = ($this->isCompany ?? false) ? ($this->taxId ?: null) : null;
        $this->user->invoice_street = $this->invoiceStreet ?: null;
        $this->user->invoice_house_nr = $this->invoiceHouseNr ?: null;
        $this->user->invoice_zip_code = $this->invoiceZipCode ?: null;
        $this->user->invoice_city = $this->invoiceCity ?: null;
        $this->user->invoice_country = $this->invoiceCountry ?: null;

        // Password
        if ($this->password) {
            $this->user->password = Hash::make($this->password);
        }

        $this->user->save();

        $this->reset(['password', 'passwordConfirmation']);

        Notification::make()
            ->title(Translation::get('account-updated-message', 'account', 'Your account has been updated'))
            ->success()
            ->send();

        $this->dispatch(
            'showAlert',
            'success',
            Translation::get('account-updated', 'account', 'Your account has been updated')
        );
    }

    public function render()
    {
        return view(config('dashed-core.site_theme', 'dashed') . '.account.account');
    }
}
