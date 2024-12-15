<section class="py-24 overflow-hidden text-primary-800">
    <x-container>
        <div class="grid gap-12 mt-12 -ml-12 divide-x-2 lg:grid-cols-2 divide-primary-800">
            <form wire:submit.prevent="login" class="flex flex-col gap-6 pl-12">
                <h1 class="text-3xl font-bold text-primary-800">{{Translation::get('login', 'login', 'Inloggen')}}</h1>

                <x-fields.input required type="email" model="loginEmail" id="email" class="w-full"
                                :label="Translation::get('email', 'login', 'E-mail')"/>
                <x-fields.input required type="password" model="loginPassword" id="password" class="w-full"
                                :label="Translation::get('password', 'login', 'Wachtwoord')"/>
                <x-fields.checkbox model="loginRememberMe" id="login_remember_me"
                                   :label="Translation::get('remember-me', 'login', 'Herinner mij')"/>
                <div>
                    <a class="text-primary-800 hover:underline"
                       href="{{AccountHelper::getForgotPasswordUrl()}}">{{Translation::get('forgot-password', 'login', 'Wachtwoord vergeten?')}}</a>
                </div>

                <button
                    class="mt-auto button button--primary-dark">{{Translation::get('login', 'login', 'Inloggen')}}</button>
            </form>

            <form class="flex flex-col gap-6 pl-12" wire:submit.prevent="register">
                <h2 class="text-3xl font-bold text-primary-800">{{Translation::get('register', 'login', 'Registreren')}}</h2>

                <x-fields.input required type="email" model="registerEmail" id="email" class="w-full"
                                :label="Translation::get('email', 'login', 'E-mail')"/>
                <x-fields.input required type="password" model="registerPassword" id="password" class="w-full"
                                :label="Translation::get('password', 'login', 'Wachtwoord')"/>
                <x-fields.input required type="password" model="registerPasswordConfirmation"
                                id="password_confirmation" class="w-full"
                                :label="Translation::get('repeat-password', 'login', 'Wachtwoord herhalen')"/>

                <button
                    class="mt-auto button button--primary-dark">{{Translation::get('register', 'login', 'Registreren')}}</button>
            </form>
        </div>
    </x-container>

    <x-dashed-core::global-blocks name="login-page"/>
</section>
