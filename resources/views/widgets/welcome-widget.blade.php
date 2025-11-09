<x-filament::widget class="filament-account-widget">
    <x-filament::card>
        @php
            $user = \Filament\Facades\Filament::auth()->user();
        @endphp

        <div class="flex h-12 items-center space-x-4 rtl:space-x-reverse">
{{--            <x-filament::user-avatar :user="$user" />--}}

            <div>
                <h2 class="text-lg font-bold tracking-tight sm:text-xl">
                    {{ 'Welkom ' . \Filament\Facades\Filament::getUserName($user) }}
                </h2>

               <div class="flex gap-4 flex-wrap mt-2">
                   <form
                       action="{{ route('filament.dashed.auth.logout') }}"
                       method="post"
                       class="text-sm"
                   >
                       @csrf

                       <button
                           type="submit"
                           @class([
                               'fi-color fi-color-primary fi-bg-color-400 hover:fi-bg-color-300 dark:fi-bg-color-600 dark:hover:fi-bg-color-700 fi-text-color-950 hover:fi-text-color-800 dark:fi-text-color-0 dark:hover:fi-text-color-0 fi-btn fi-size-md  fi-ac-btn-action',
                               'dark:text-gray-300 dark:hover:text-primary-500' => config('filament.dark_mode'),
                           ])
                       >
                           <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                               <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                           </svg>

                           <span>{{ 'Uitloggen' }}</span>
                       </button>
                   </form>

                   <a href="{{ url('/') }}"
                      target="_blank"
                       type="submit"
                       @class([
                           'fi-color fi-color-primary fi-bg-color-400 hover:fi-bg-color-300 dark:fi-bg-color-600 dark:hover:fi-bg-color-700 fi-text-color-950 hover:fi-text-color-800 dark:fi-text-color-0 dark:hover:fi-text-color-0 fi-btn fi-size-md  fi-ac-btn-action',
                           'dark:text-gray-300 dark:hover:text-primary-500' => config('filament.dark_mode'),
                       ])
                   >
                       <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                           <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                       </svg>

                       <span>{{ 'Bekijk website' }}</span>
                   </a>
               </div>
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>
