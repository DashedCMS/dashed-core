<div>
    <x-container>
        <div class="grid md:grid-cols-6 gap-8 py-16 sm:py-24">
            <div class="md:col-span-2">
                <nav class="grid space-y-2" aria-label="Tabs">
                    <a href="{{ AccountHelper::getAccountUrl() }}"
                       class="button button--primary">
                        {{ Translation::get('my-account', 'account', 'Mijn account') }}
                    </a>
                    <a href="{{ EcommerceAccountHelper::getAccountOrdersUrl() }}"
                       class="button button--secondary">
                        {{ Translation::get('my-orders', 'account', 'Mijn bestellingen') }}
                    </a>
                    <a href="{{ AccountHelper::getLogoutUrl() }}"
                       class="button button--secondary">
                        {{ Translation::get('logout', 'login', 'Uitloggen') }}
                    </a>
                </nav>
            </div>

            <div class="md:col-span-4">
                <h1 class="text-2xl">
                    {{ Translation::get('welcome', 'account', 'Welkom :name:', 'text', ['name' => $user->name]) }}
                </h1>

                <form class="mt-6 space-y-8" wire:submit.prevent="submit">

                    {{-- CONTACT --}}
                    <div class="bg-white/60 rounded-xl p-4 md:p-6 border border-black/5">
                        <h2 class="text-lg font-bold text-primary mb-4">
                            {{ Translation::get('contact-info', 'account', 'Contact') }}
                        </h2>

                        <div class="grid md:grid-cols-2 gap-4">
                            <div class="md:col-span-2">
                                <x-fields.input
                                    disabled
                                    placeholder="{{ Translation::get('email', 'account', 'E-mail') }}"
                                    type="email"
                                    model="email"
                                    id="email"
                                />
                            </div>

                            <x-fields.input
                                type="text"
                                model="firstName"
                                id="firstName"
                                placeholder="{{ Translation::get('first-name', 'account', 'Voornaam') }}"
                            />

                            <x-fields.input
                                type="text"
                                model="lastName"
                                id="lastName"
                                placeholder="{{ Translation::get('last-name', 'account', 'Achternaam') }}"
                            />

                            <x-fields.input
                                type="text"
                                model="phoneNumber"
                                id="phoneNumber"
                                placeholder="{{ Translation::get('phone-number', 'account', 'Telefoonnummer') }}"
                            />

                            <div class="grid grid-cols-2 gap-4 md:col-span-2">
                                <x-fields.input
                                    type="date"
                                    model="dateOfBirth"
                                    id="dateOfBirth"
                                    placeholder="{{ Translation::get('date-of-birth', 'account', 'Geboortedatum') }}"
                                />

                                <x-fields.select
                                    model="gender"
                                    id="gender"
                                >
                                    <option value="">{{ Translation::get('choose', 'account', 'Kies geslacht') }}</option>
                                    <option value="m">{{ Translation::get('male', 'account', 'Man') }}</option>
                                    <option value="f">{{ Translation::get('female', 'account', 'Vrouw') }}</option>
                                </x-fields.select>
                            </div>
                        </div>
                    </div>

                    {{-- ADRES --}}
                    <div class="bg-white/60 rounded-xl p-4 md:p-6 border border-black/5">
                        <h2 class="text-lg font-bold text-primary mb-4">
                            {{ Translation::get('shipping-address', 'account', 'Adres') }}
                        </h2>

                        <div class="grid md:grid-cols-2 gap-4">
                            <x-fields.input
                                type="text"
                                model="street"
                                id="street"
                                placeholder="{{ Translation::get('street', 'account', 'Straat') }}"
                            />

                            <x-fields.input
                                type="text"
                                model="houseNr"
                                id="houseNr"
                                placeholder="{{ Translation::get('house-number', 'account', 'Huisnummer') }}"
                            />

                            <x-fields.input
                                type="text"
                                model="zipCode"
                                id="zipCode"
                                placeholder="{{ Translation::get('zip-code', 'account', 'Postcode') }}"
                            />

                            <x-fields.input
                                type="text"
                                model="city"
                                id="city"
                                placeholder="{{ Translation::get('city', 'account', 'Stad') }}"
                            />

                            <x-fields.input
                                type="text"
                                model="country"
                                id="country"
                                placeholder="{{ Translation::get('country', 'account', 'Land') }}"
                                class="md:col-span-2"
                            />
                        </div>
                    </div>

                    {{-- BEDRIJF --}}
                    <div class="bg-white/60 rounded-xl p-4 md:p-6 border border-black/5">
                        <h2 class="text-lg font-bold text-primary mb-2">
                            {{ Translation::get('company', 'account', 'Bedrijf') }}
                        </h2>
                        <p class="text-sm text-black/60 mb-4">
                            {{ Translation::get('company-sub', 'account', 'Alleen invullen als je zakelijk bestelt') }}
                        </p>

                        <div class="space-y-4">
                            <x-fields.checkbox
                                model="isCompany"
                                id="isCompany"
                                :label="Translation::get('order-as-company', 'account', 'Ik bestel als bedrijf')"
                            />

                            @if($isCompany)
                                <div class="grid md:grid-cols-2 gap-4">
                                    <x-fields.input
                                        type="text"
                                        model="company"
                                        id="company"
                                        placeholder="{{ Translation::get('company-name', 'account', 'Bedrijfsnaam') }}"
                                    />

                                    <x-fields.input
                                        type="text"
                                        model="taxId"
                                        id="taxId"
                                        placeholder="{{ Translation::get('tax-id', 'account', 'BTW ID') }}"
                                    />
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- FACTUURADRES --}}
                    <div class="bg-white/60 rounded-xl p-4 md:p-6 border border-black/5">
                        <h2 class="text-lg font-bold text-primary mb-4">
                            {{ Translation::get('invoice-address', 'account', 'Factuuradres') }}
                        </h2>

                        <div class="grid md:grid-cols-2 gap-4">
                            <x-fields.input
                                type="text"
                                model="invoiceStreet"
                                id="invoiceStreet"
                                placeholder="{{ Translation::get('street', 'account', 'Straat') }}"
                            />

                            <x-fields.input
                                type="text"
                                model="invoiceHouseNr"
                                id="invoiceHouseNr"
                                placeholder="{{ Translation::get('house-number', 'account', 'Huisnummer') }}"
                            />

                            <x-fields.input
                                type="text"
                                model="invoiceZipCode"
                                id="invoiceZipCode"
                                placeholder="{{ Translation::get('zip-code', 'account', 'Postcode') }}"
                            />

                            <x-fields.input
                                type="text"
                                model="invoiceCity"
                                id="invoiceCity"
                                placeholder="{{ Translation::get('city', 'account', 'Stad') }}"
                            />

                            <x-fields.input
                                type="text"
                                model="invoiceCountry"
                                id="invoiceCountry"
                                placeholder="{{ Translation::get('country', 'account', 'Land') }}"
                                class="md:col-span-2"
                            />
                        </div>
                    </div>

                    {{-- PASSWORD --}}
                    <div class="bg-white/60 rounded-xl p-4 md:p-6 border border-black/5">
                        <h2 class="text-lg font-bold text-primary mb-4">
                            {{ Translation::get('security', 'account', 'Beveiliging') }}
                        </h2>

                        <div class="grid md:grid-cols-2 gap-4">
                            <x-fields.input
                                placeholder="{{ Translation::get('password', 'account', 'Wachtwoord') }}"
                                type="password"
                                model="password"
                                id="password"
                                :helperText="Translation::get('password-not-changed-if-empty', 'account', 'Als je geen wachtwoord invoert, veranderd dit niet!')"
                            />

                            <x-fields.input
                                placeholder="{{ Translation::get('repeat-password', 'account', 'Wachtwoord herhalen') }}"
                                type="password"
                                model="passwordConfirmation"
                                id="passwordConfirmation"
                            />
                        </div>
                    </div>

                    <div class="flex">
                        <button class="button button--primary">
                            {{ Translation::get('update-account', 'account', 'Account bijwerken') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </x-container>

    <div wire:ignore>
        <x-dashed-core::global-blocks name="account-page"/>
    </div>
</div>
