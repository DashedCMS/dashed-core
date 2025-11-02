<footer class="text-white relative bg-cover bg-primary-dark/25 border-t-2 border-primary-light" style="background-image: url('{{ mediaHelper()->getSingleMedia(Translation::get('background-image', 'footer', null, 'image'), [
    'widen' => 1000,
])->url ?? '' }}')">
    <div class="py-12 bg-primary-dark/80">
        <x-container>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">

                <div class="col-span-1 md:col-span-2">
                    <h2 class="text-2xl font-bold mb-4">{{ Translation::get('sign-up-for-our-newsletter', 'footer', 'Meld je aan voor onze nieuwsbrief') }}</h2>
                    <p class="text-gray-400 mb-6">
                        {{ Translation::get('sign-up-for-our-newsletter-content', 'footer', 'Krijg updates over onze voorraad, kortingen en meer') }}
                    </p>
{{--                    <livewire:dashed-forms.form :formId="2"/>--}}
                    @php($paymentMethods = ShoppingCart::getPaymentMethods(skipTotalCheck: true))
                    @if(count($paymentMethods))
                        <div class="flex items-center gap-2 mt-4">
                            <div class="flex items-center justify-center text-sm gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"
                                     class="size-6 text-green-800">
                                    <path fill-rule="evenodd"
                                          d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z"
                                          clip-rule="evenodd"/>
                                </svg>

                                <h3 class="font-normal">{{ Translation::get('pay-safe-with', 'products', 'Betaal veilig met') }}</h3>
                            </div>
                            <div class="flex gap-2 flex-wrap items-center justify-center">
                                @foreach($paymentMethods as $paymentMethod)
                                    @if($paymentMethod->image)
                                        <x-dashed-files::image
                                            :mediaId="$paymentMethod->image"
                                            :alt="$paymentMethod->name"
                                            :manipulations="[ 'widen' => 100 ]"
                                            class="w-8"
                                        />
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                @foreach (range(1, 2) as $i)
                    <div>
                        <p class="font-semibold mb-4">{{ Translation::get('footer-widget-menu-' . $i, 'footer', 'Footer menu ' . $i) }}</p>

                        <ul class="space-y-2">
                            @foreach (Menus::getMenuItems('footer-menu-' . $i) as $menuItem)
                                <li>
                                    <a
                                        class="text-gray-400 hover:text-primary-light flex gap-1 items-center group"
                                        href="{{ $menuItem['url'] }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                             stroke-width="1.5" stroke="currentColor" class="size-6">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                  d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                                        </svg>

                                        <span class="group-hover:ml-2 trans">{{ $menuItem['name'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>

            <div class="mt-12 border-t border-primary-light pt-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div class="flex items-center">
                        <a href="{{ url('/') }}">
                            <x-dashed-files::image
                                class="w-full"
                                config="dashed"
                                :mediaId="Translation::get('light-logo', 'branding', null, 'image')"
                                :alt="Customsetting::get('site_name')"
                                :manipulations="[
                            'widen' => 300,
                        ]"
                            />
                        </a>
                    </div>

                    <div>
                        <h3 class="font-semibold mb-4">{{ Translation::get('contact-us', 'footer', 'Neem contact met ons op') }}</h3>
                        <ul class="space-y-2 text-gray-400">
                            <li><a class="trans hover:text-primary-light" href="tel:{{ Customsetting::get('company_phone_number') }}"><span
                                        class="font-bold">{{ Translation::get('phone-number', 'footer', 'Telefoon') }}:</span> {{ Customsetting::get('company_phone_number') }}</a>
                            </li>
                            <li><a class="trans hover:text-primary-light" href="mailto:{{ Customsetting::get('site_to_email') }}"><span class="font-bold">{{ Translation::get('email', 'footer', 'E-mail') }}:</span> {{ Customsetting::get('site_to_email') }}</a>
                            </li>
                            <li>
                                <a class="trans hover:text-primary-light" href="https://maps.google.com?q={{ Customsetting::get('company_street') }} {{ Customsetting::get('company_street_number') }} {{ Customsetting::get('company_postal_code') }} {{ Customsetting::get('company_city') }}"
                                   target="_blank" rel="nofollow noopener"><span class="font-bold">{{ Translation::get('address', 'footer', 'Adres') }}:</span> {{ Customsetting::get('company_street') }} {{ Customsetting::get('company_street_number') . ', ' }}{{ Customsetting::get('company_postal_code') }} {{ Customsetting::get('company_city') . ', ' }}{{ Customsetting::get('company_country') }}</a></li>
                            <li>
                                <span class="font-bold">{{ Translation::get('openingtimes-1', 'footer', 'Ma t/m vr') }}:</span> {{ Translation::get('openingtimes-1-content', 'footer', '8.00 - 17.00') }}
                            </li>
                            <li>
                                <span class="font-bold">{{ Translation::get('openingtimes-2', 'footer', 'Zat') }}:</span> {{ Translation::get('openingtimes-2-content', 'footer', '8.00 - 12.00 (op afspraak)') }}
                            </li>
                        </ul>
                    </div>

                    <div class="gap-4 flex flex-col justify-start md:col-span-2">
                        <p class="text-gray-400 text-balance max-w-[80%]">
                            {{ Translation::get('footer-description', 'footer', 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it') }}
                        </p>
                        <div class="flex gap-2 flex-wrap">
                            <a
                                name="Instagram"
                                target="_blank"
                                class="size-8 grid place-content-center bg-primary-light text-primary-dark rounded-full hover:bg-primary-dark hover:text-primary-light trans transition"
                                href="{{Translation::get('instagram-url', 'socials', 'https://www.instagram.com')}}"
                            >
                                <svg class="size-4" fill="currentColor" role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><title>Instagram</title><path d="M7.0301.084c-1.2768.0602-2.1487.264-2.911.5634-.7888.3075-1.4575.72-2.1228 1.3877-.6652.6677-1.075 1.3368-1.3802 2.127-.2954.7638-.4956 1.6365-.552 2.914-.0564 1.2775-.0689 1.6882-.0626 4.947.0062 3.2586.0206 3.6671.0825 4.9473.061 1.2765.264 2.1482.5635 2.9107.308.7889.72 1.4573 1.388 2.1228.6679.6655 1.3365 1.0743 2.1285 1.38.7632.295 1.6361.4961 2.9134.552 1.2773.056 1.6884.069 4.9462.0627 3.2578-.0062 3.668-.0207 4.9478-.0814 1.28-.0607 2.147-.2652 2.9098-.5633.7889-.3086 1.4578-.72 2.1228-1.3881.665-.6682 1.0745-1.3378 1.3795-2.1284.2957-.7632.4966-1.636.552-2.9124.056-1.2809.0692-1.6898.063-4.948-.0063-3.2583-.021-3.6668-.0817-4.9465-.0607-1.2797-.264-2.1487-.5633-2.9117-.3084-.7889-.72-1.4568-1.3876-2.1228C21.2982 1.33 20.628.9208 19.8378.6165 19.074.321 18.2017.1197 16.9244.0645 15.6471.0093 15.236-.005 11.977.0014 8.718.0076 8.31.0215 7.0301.0839m.1402 21.6932c-1.17-.0509-1.8053-.2453-2.2287-.408-.5606-.216-.96-.4771-1.3819-.895-.422-.4178-.6811-.8186-.9-1.378-.1644-.4234-.3624-1.058-.4171-2.228-.0595-1.2645-.072-1.6442-.079-4.848-.007-3.2037.0053-3.583.0607-4.848.05-1.169.2456-1.805.408-2.2282.216-.5613.4762-.96.895-1.3816.4188-.4217.8184-.6814 1.3783-.9003.423-.1651 1.0575-.3614 2.227-.4171 1.2655-.06 1.6447-.072 4.848-.079 3.2033-.007 3.5835.005 4.8495.0608 1.169.0508 1.8053.2445 2.228.408.5608.216.96.4754 1.3816.895.4217.4194.6816.8176.9005 1.3787.1653.4217.3617 1.056.4169 2.2263.0602 1.2655.0739 1.645.0796 4.848.0058 3.203-.0055 3.5834-.061 4.848-.051 1.17-.245 1.8055-.408 2.2294-.216.5604-.4763.96-.8954 1.3814-.419.4215-.8181.6811-1.3783.9-.4224.1649-1.0577.3617-2.2262.4174-1.2656.0595-1.6448.072-4.8493.079-3.2045.007-3.5825-.006-4.848-.0608M16.953 5.5864A1.44 1.44 0 1 0 18.39 4.144a1.44 1.44 0 0 0-1.437 1.4424M5.8385 12.012c.0067 3.4032 2.7706 6.1557 6.173 6.1493 3.4026-.0065 6.157-2.7701 6.1506-6.1733-.0065-3.4032-2.771-6.1565-6.174-6.1498-3.403.0067-6.156 2.771-6.1496 6.1738M8 12.0077a4 4 0 1 1 4.008 3.9921A3.9996 3.9996 0 0 1 8 12.0077"/></svg>
                            </a>
                            <a
                                name="Facebook"
                                target="_blank"
                                class="size-8 grid place-content-center bg-primary-light text-primary-dark rounded-full hover:bg-primary-dark hover:text-primary-light trans transition"
                                href="{{Translation::get('facebook-url', 'socials', 'https://www.facebook.com')}}"
                            >
                                <svg fill="currentColor" class="size-4" role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><title>Facebook</title><path d="M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036 26.805 26.805 0 0 0-.733-.009c-.707 0-1.259.096-1.675.309a1.686 1.686 0 0 0-.679.622c-.258.42-.374.995-.374 1.752v1.297h3.919l-.386 2.103-.287 1.564h-3.246v8.245C19.396 23.238 24 18.179 24 12.044c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.628 3.874 10.35 9.101 11.647Z"/></svg>
                            </a>
                            <a
                                name="TikTok"
                                target="_blank"
                                class="size-8 grid place-content-center bg-primary-light text-primary-dark rounded-full hover:bg-primary-dark hover:text-primary-light trans transition"
                                href="{{Translation::get('tiktok-url', 'socials', 'https://www.tiktok.com')}}"
                            >
                                <svg fill="currentColor" class="size-4" role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><title>TikTok</title><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center flex-wrap gap-x-2 gap-y-4 text-center mt-6 pt-6 border-t border-primary-light">
                <p class="text-sm text-gray-400 grow w-full md:w-auto">
                    ©{{ now()->format('Y') }} {{ Customsetting::get('site_name') }} - {{ Translation::get('realisation-by', 'footer', 'Design en realisatie door') }} <a
                        class="uppercase italic font-bold text-primary-light hover:underline"
                        href="https://dashed.nl?ref={{ url('/') }}" rel="nofollow">DASHED</a></p>
            </div>
        </x-container>
    </div>
</footer>
