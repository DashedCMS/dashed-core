<div>
    <x-container>
        <div class="w-full max-w-(--breakpoint-xl) px-4 mx-auto sm:px-8">
            <div class="py-0 sm:py-8 mx-auto grid grid-cols-1 max-w-xl">
                <form wire:submit.prevent="submit" class="space-y-6 flex flex-col mt-auto">
                    <h2 class="font-display font-bold text-xl">{{Translation::get('forgot-password', 'login', 'Wachtwoord vergeten?')}}</h2>
                    <x-fields.input required type="email" model="email" id="email" class="w-full" :label="Translation::get('email', 'login', 'E-mail')" :helperText="Translation::get('forgot-password-description', 'login', 'Vul je email in en we mailen je een link om je wachtwoord te resetten.')" />
                    <button class="button button--primary-dark mt-auto">
                        {{Translation::get('request-password-reset', 'login', 'Vraag nieuw wachtwoord aan')}}
                    </button>
                </form>
                <div class="mt-4 text-center">
                    <a class="text-primary-500 hover:text-primary-800 trans" href="{{AccountHelper::getAccountUrl()}}">
                        {{Translation::get('login', 'login', 'Login')}}
                    </a>
                </div>
            </div>
        </div>
    </x-container>

    <x-dashed-core::global-blocks name="forgot-password-page"/>

</div>
