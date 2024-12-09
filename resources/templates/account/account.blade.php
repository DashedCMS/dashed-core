<x-container>
    <div class="grid md:grid-cols-6 gap-8 py-16 sm:py-24">
        <div class="md:col-span-2">
            <nav class="grid space-y-2" aria-label="Tabs">
                <a href="{{AccountHelper::getAccountUrl()}}"
                   class="button button--primary-light">
                    {{Translation::get('my-account', 'account', 'Mijn account')}}
                </a>
{{--                <a href="{{EcommerceAccountHelper::getAccountOrdersUrl()}}"--}}
{{--                   class="button button--primary-dark">--}}
{{--                    {{Translation::get('my-orders', 'account', 'Mijn bestellingen')}}--}}
{{--                </a>--}}
                <a href="{{AccountHelper::getLogoutUrl()}}"
                   class="button button--primary-dark">
                    {{Translation::get('logout', 'login', 'Uitloggen')}}
                </a>
            </nav>
        </div>
        <div class="md:col-span-4">
            <h1 class="text-2xl">{{Translation::get('welcome', 'account', 'Welkom :name:', 'text', [
    'name' => $user->name,
])}}</h1>
            <form class="mt-4 space-y-4" wire:submit.prevent="submit">
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <x-fields.input
                            disabled
                            placeholder="{{Translation::get('email', 'account', 'E-mail')}}"
                            type="email"
                            model="email"
                            id="email"
                        />
                    </div>
                    <div class="">
                        <x-fields.input
                            type="text"
                            model="firstName"
                            id="firstName"
                            placeholder="{{Translation::get('first-name', 'account', 'Voornaam')}}"
                        />
                    </div>
                    <div class="">
                        <x-fields.input
                            type="text"
                            model="lastName"
                            id="lastName"
                            placeholder="{{Translation::get('last-name', 'account', 'Achternaam')}}"
                        />
                    </div>
                    <div class="">
                        <x-fields.input
                            placeholder="{{Translation::get('password', 'account', 'Wachtwoord')}}"
                            type="password"
                            model="password"
                            id="password"
                            :helperText="Translation::get('password-not-changed-if-empty', 'account', 'Als je geen wachtwoord invoert, veranderd dit niet!')"
                        />
                    </div>
                    <div class="">
                        <x-fields.input
                            placeholder="{{Translation::get('repeat-password', 'account', 'Wachtwoord herhalen')}}"
                            type="password"
                            model="passwordConfirmation"
                            id="passwordConfirmation"
                        />
                    </div>
                </div>
                <div class="flex">
                    <button
                        class="button button--primary-dark">
                        {{Translation::get('update-account', 'account', 'Account bijwerken')}}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-container>

<x-dashed-core::global-blocks name="account-page"/>
