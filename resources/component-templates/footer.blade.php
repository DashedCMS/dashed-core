<footer class="py-12">
    <x-container>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-8">
            <header class="col-span-2">
                <div class="flex flex-wrap items-center justify-center gap-4">
                    <a href="/" class="w-24">
                        <x-drift::image
                                class="w-24"
                                config="dashed"
                                :path="$logo"
                                :alt="Customsetting::get('site_name')"
                                :manipulations="[
                            'widen' => 300,
                        ]"
                        />
                    </a>

                    <p class="mt-4 text-gray-600 text-balance max-w-[80%]">
                        {{ Translation::get('footer-description', 'footer', 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it') }}
                    </p>
                </div>
            </header>

            @foreach (range(1, 2) as $i)
                <div>
                    <p class="text-xl tracking-tight">{{ Translation::get('footer-widget-menu-' . $i, 'footer', 'Footer menu ' . $i) }}</p>

                    <ul class="mt-4 space-y-3">
                        @foreach (Menus::getMenuItems('footer-menu-' . $i) as $menuItem)
                            <li>
                                <a
                                        class="text-link flex gap-1 items-center group"
                                        href="{{ $menuItem['url'] }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                    </svg>

                                    <span class="group-hover:ml-2 trans">{{ $menuItem['name'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
        <hr class="my-12">
        <div class="w-full flex flex-wrap gap-8 justify-between items-center">
            <div class="space-y-4 text-gray-600">
                <div class="text-link flex gap-2 items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>

                    <a href="https://maps.google.com?q={{ Customsetting::get('company_street') }} {{ Customsetting::get('company_street_number') }} {{ Customsetting::get('company_postal_code') }} {{ Customsetting::get('company_city') }}" target="_blank" rel="nofollow noopener">
                        <p>{{ Customsetting::get('company_street') }} {{ Customsetting::get('company_street_number') }}</p>
                        <p>{{ Customsetting::get('company_postal_code') }} {{ Customsetting::get('company_city') }}</p>
                    </a>
                </div>

                <p class="text-link flex gap-2 items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                    </svg>

                    <a href="tel:{{ Customsetting::get('company_phone_number') }}">{{ Customsetting::get('company_phone_number') }}</a>
                </p>

                <p class="text-link flex gap-2 items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                    </svg>

                    <a href="mailto:{{ Customsetting::get('site_to_email') }}">{{ Customsetting::get('site_to_email') }}</a>
                </p>
            </div>
            <div class="">
                <h3 class="text-lg font-bold">Meld je aan voor onze nieuwsbrief</h3>
                <p class="text-sm text-gray-500">Krijg updates over onze voorraad, nieuwe vissen of andere handige weetjes</p>
                <form class="mt-2 flex sm:max-w-md">
                    <label for="email-address" class="sr-only">Email address</label>
                    <input id="email-address" type="text" autocomplete="email" required="" class="w-full min-w-0 appearance-none rounded-md border border-white bg-white px-4 py-2 text-base text-gray-900 placeholder-gray-500 shadow-sm focus:border-white focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-gray-900">
                    <div class="ml-4 flex-shrink-0">
                        <button type="submit" class="flex w-full items-center justify-center rounded-md border border-transparent bg-primary-600 px-4 py-2 text-base font-bold text-white shadow-sm hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 focus:ring-offset-gray-900">Sign up</button>
                    </div>
                </form>
            </div>
            <div class="w-[300px] h-[300px] bg-primary"></div>
        </div>
        </x-container>
</footer>

<div class="py-8 bg-gray-100">
    <x-container>
        <div class="flex items-center flex-wrap gap-x-2 gap-y-4">
            <p class="text-sm text-gray-500 grow w-full md:w-auto">
                ©{{ now()->format('Y') }} {{ Customsetting::get('site_name') }} - Realisatie door <a class="uppercase italic font-bold text-primary-500 hover:text-primary-700" href="https://dashed.nl?ref={{ url('/') }}" rel="nofollow">DASHED</a></p>

{{--            <a--}}
{{--                    class="size-8 grid place-content-center bg-primary-400 text-white rounded-full hover:bg-primary-800 transition"--}}
{{--                    href="{{Translation::get('twitter-url', 'socials', 'https://www.twitter.com')}}"--}}
{{--                    rel="nofollow"--}}
{{--            >--}}
{{--                <x-lucide-twitter class="size-4"/>--}}
{{--            </a>--}}

{{--            <a--}}
{{--                    class="size-8 grid place-content-center bg-primary-400 text-white rounded-full hover:bg-primary-800 transition"--}}
{{--                    href="{{Translation::get('facebook-url', 'socials', 'https://www.facebook.com')}}"--}}
{{--                    rel="nofollow"--}}
{{--            >--}}
{{--                <x-lucide-facebook class="size-4"/>--}}
{{--            </a>--}}

            <a
                    class="size-8 grid place-content-center bg-primary-400 text-white rounded-full hover:bg-primary-800 transition"
                    href="{{Translation::get('instagram-url', 'socials', 'https://www.instagram.com/')}}"
            >
                <x-lucide-instagram class="size-4"/>
            </a>
        </div>
    </x-container>
</div>

<div class="h-2 bg-gradient-to-r from-primary-500 to-primary-200"></div>
