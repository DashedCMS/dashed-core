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
            <form class="mt-4 space-y-4" method="post" action="{{AccountHelper::getUpdateAccountUrl()}}">
                @csrf
                <div class="grid grid-cols-4 md:grid-cols-8 gap-4">
                    <div class="col-span-4">
                        <input type="email" disabled class="form-input" id="email" name="email"
                               value="{{old('email') ? old('email') : $user->email ?? ''}}"
                               placeholder="{{Translation::get('email', 'account', 'E-mail')}}">
                    </div>
                </div>
                <div class="grid grid-cols-4 md:grid-cols-8 gap-4">
                    <div class="col-span-4">
                        <input type="text" class="form-input" id="first_name" name="first_name"
                               value="{{old('first_name') ? old('first_name') : $user->first_name ?? ''}}"
                               placeholder="{{Translation::get('first-name', 'account', 'First name')}}">
                    </div>
                    <div class="col-span-4">
                        <input type="text" class="form-input" id="last_name" name="last_name"
                               value="{{old('last_name') ? old('last_name') : $user->last_name ?? ''}}"
                               placeholder="{{Translation::get('last-name', 'account', 'Last name')}}">
                    </div>
                </div>
                <div class="grid grid-cols-4 md:grid-cols-8 gap-4">
                    <div class="col-span-4">
                        <input type="password" class="form-input" id="password" name="password"
                               placeholder="{{Translation::get('password', 'account', 'Password')}}">
                        <small>
                            {{Translation::get('password-not-changed-if-empty', 'account', 'If you do not enter a password this will not be changed!')}}
                        </small>
                    </div>
                    <div class="col-span-4">
                        <input type="password" class="form-input" id="password_confirmation"
                               name="password_confirmation"
                               placeholder="{{Translation::get('repeat-password', 'account', 'Repeat password')}}">
                    </div>
                </div>
                <div class="flex">
                    <button
                            class="button-white-on-primary">
                        {{Translation::get('update-account', 'account', 'Update account')}}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-container>