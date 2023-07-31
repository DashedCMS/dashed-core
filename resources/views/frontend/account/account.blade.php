<x-container>
    <div class="grid grid-cols-6 gap-8 py-16 sm:py-24">
        <div class="col-span-2">
            <nav class="grid space-y-2" aria-label="Tabs">
                <a href="{{AccountHelper::getAccountUrl()}}"
                   class="button button-primary-ghost">
                    {{Translation::get('my-account', 'account', 'My account')}}
                </a>
                <a href="{{EcommerceAccountHelper::getAccountOrdersUrl()}}"
                   class="button button-white-on-primary">
                    {{Translation::get('my-orders', 'account', 'My orders')}}
                </a>
                <a href="{{AccountHelper::getLogoutUrl()}}"
                   class="button button-white-on-primary">
                    {{Translation::get('logout', 'login', 'Logout')}}
                </a>
            </nav>
        </div>
        <div class="col-span-4">
            <h1 class="text-2xl">{{Translation::get('welcome', 'account', 'Welcome')}} {{$user->name}}</h1>
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
                            placeholder="{{Translation::get('first-name', 'account', 'First name')}}"
                        />
                    </div>
                    <div class="">
                        <x-fields.input
                            type="text"
                            model="lastName"
                            id="lastName"
                            placeholder="{{Translation::get('last-name', 'account', 'Last name')}}"
                        />
                    </div>
                    <div class="">
                        <x-fields.input
                            placeholder="{{Translation::get('password', 'account', 'Password')}}"
                            type="password"
                            model="password"
                            id="password"
                            :helperText="Translation::get('password-not-changed-if-empty', 'account', 'If you do not enter a password this will not be changed!')"
                        />
                    </div>
                    <div class="">
                        <x-fields.input
                            placeholder="{{Translation::get('repeat-password', 'account', 'Repeat password')}}"
                            type="password"
                            model="passwordConfirmation"
                            id="passwordConfirmation"
                        />
                    </div>
                </div>
                <div class="flex">
                    <button
                        class="button button-white-on-primary">
                        {{Translation::get('update-account', 'account', 'Update account')}}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-container>
