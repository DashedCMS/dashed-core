<x-container>
    <div class="w-full max-w-screen-xl px-4 mx-auto sm:px-8">
        <div class="py-0 sm:py-8 mx-auto grid grid-cols-1 max-w-xl">
            <form wire:submit.prevent="submit" class="space-y-6 flex flex-col mt-auto">
                <h2 class="font-display font-bold text-xl">{{Translation::get('reset-password', 'login', 'Wachtwoord herstellen')}}</h2>

                <x-fields.input required type="password" model="password" id="password" class="w-full" :label="Translation::get('password', 'login', 'Wachtwoord')" />
                <x-fields.input required type="password" model="passwordConfirmation" id="password" class="w-full" :label="Translation::get('repeat-password', 'login', 'Wachtwoord herhalen')" />
                <button
                    class="button button--primary-dark mt-auto">{{Translation::get('reset-password-now', 'login', 'Wachtwoord opnieuw instellen')}}</button>
            </form>
            <div class="mt-4 text-center">
                <a class="text-primary-500 hover:text-primary-800 trans"
                   href="{{AccountHelper::getAccountUrl()}}">{{Translation::get('login', 'login', 'Login')}}</a>
            </div>
        </div>
    </div>
</x-container>

<x-dashed-core::global-blocks name="reset-password-page"/>
