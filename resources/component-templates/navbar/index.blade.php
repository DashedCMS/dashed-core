<div class="bg-gradient-to-r from-primary-800 to-primary-500 px-4 md:px-8" wire:ignore>
    <x-container>
        <div class="py-2 swiper swiper-usps">
            <ul
                    class="gap-4 text-xs md:text-sm font-bold text-white swiper-wrapper">
                <li class="swiper-slide">
                    <div class="flex items-center justify-center gap-2 drop-shadow">
                        <x-lucide-gift class="size-4"/>

                        <div class="font-bold">{!! Translation::get('usp-1', 'navbar', 'Gratis verzending vanaf &euro;99') !!}</div>
                    </div>
                </li>

                <li class="swiper-slide">
                    <div class="flex items-center justify-center gap-2 drop-shadow">
                        <x-lucide-wallet class="size-4"/>

                        <div class="font-bold">{!! Translation::get('usp-2', 'navbar', 'Veilig betalen') !!}</div>
                    </div>
                </li>

                <li class="swiper-slide">
                    <div class="flex items-center justify-center gap-2 drop-shadow">
                        <x-lucide-heart-handshake class="size-4"/>

                        <div class="font-bold">{!! Translation::get('usp-3', 'navbar', 'Persoonlijke service') !!}</div>
                    </div>
                </li>

                <li class="swiper-slide">
                    <div class="flex items-center justify-center gap-2 drop-shadow">
                        <x-lucide-store class="size-4"/>

                        <div class="font-bold">{!! Translation::get('usp-4', 'navbar', 'Alleen de beste kwaliteit') !!}</div>
                    </div>
                </li>
            </ul>
        </div>
    </x-container>
</div>

@php($headerInset = isset($page) && ($page->contentBlocks['header_inset'] ?? false) ? true : false)
<header
        class=" @if($headerInset) bg-black/70 @else bg-black @endif text-white backdrop-blur backdrop-saturate-200 sticky top-0 z-40 transform-gpu ring-1 ring-gray-900/5 py-4"
        x-data="{ open: false }"
>
    <div
            x-cloak
            x-show="open"
            x-transition.opacity.scale.origin.top
            class="absolute inset-x-0 bg-black top-24 ring-1 ring-gray-950/5 z-40 max-h-[calc(100dvh-5rem)] overflow-y-auto lg:hidden"
    >
        <x-container>
            <ul class="divide-y divide-gray-950/5 -mx-4">
                @foreach (Menus::getMenuItems('main-menu') as $menuItem)
                    <li>
                        <details class="group open:border-b-2 open:border-primary-500">
                            <summary
                                    class="font-bold p-4 md:hover:bg-gray-100 transition text-sm marker:content-none flex items-center gap-2"
                            >
                                @if($menuItem['hasChilds'])
                                    <x-lucide-chevron-right
                                            class="w-4 h-4 text-white transition group-open:rotate-90 group-open:text-primary-500"
                                    />

                                    <span>{{ $menuItem['name'] }}</span>
                                @else
                                    <a href="{{ $menuItem['url'] }}">{{ $menuItem['name'] }}</a>
                                @endif
                            </summary>

                            @if($menuItem['hasChilds'])
                                <ul class="px-4 pb-4">
                                    @foreach ($menuItem['childs'] as $child)
                                        <li>
                                            <a class="p-4 trans text-sm transform hover:text-primary-500 hover:translate-x-2 md:hover:bg-gray-100 flex gap-1 items-center"
                                               href="{{ $child['url'] }}"
                                            >
                                                <x-lucide-chevron-right
                                                        class="w-4 h-4 text-white transition"
                                                />
                                                <span>{{ $child['name'] }}</span>
                                            </a>
                                        </li>

                                        @if($child['hasChilds'])
                                            <ul class="px-4 pb-4">
                                                @foreach ($child['childs'] as $baby)
                                                    <li>
                                                        <a class="p-4 trans text-sm transform hover:text-primary-500 hover:translate-x-2 md:hover:bg-gray-100 flex gap-1 items-center"
                                                           href="{{ $baby['url'] }}"
                                                        >
                                                            <x-lucide-chevron-right
                                                                    class="w-4 h-4 text-white transition"
                                                            />
                                                            <span>{{ $baby['name'] }}</span>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif
                        </details>
                    </li>
                @endforeach
            </ul>
        </x-container>
    </div>

    <x-container>
        <nav class="h-16 items-center flex gap-8 justify-start">
            <a href="/">
                <x-drift::image
                        class="h-12"
                        config="dashed"
                        :path="Translation::get('light-logo', 'branding', null, 'image')"
                        :alt="Customsetting::get('site_name')"
                        :manipulations="[
                            'widen' => 300,
                        ]"
                />
            </a>

            <ul class="hidden lg:flex items-center text-white text-sm justify-center">
                @foreach (Menus::getMenuItems('main-menu') as $menuItem)
                    <x-navbar.link
                            :menuItem="$menuItem">
                        @if($menuItem['hasChilds'])
                            @if($menuItem['contentBlocks']['normal_submenu'] ?? false)
                                <div class="relative">
                                    <x-navbar.normal-menu :menuItem="$menuItem"/>
                                </div>
                            @else
                                <x-navbar.mega-menu :menuItem="$menuItem"/>
                            @endif
                        @endif
                    </x-navbar.link>
                @endforeach
            </ul>

            <div class="flex flex-1 items-center justify-end">
                <div class="flex items-center lg:ml-8">
                    <div class="flex">
                        {{--                        <div class="hidden lg:flex">--}}
                        <livewire:products.searchbar/>
                        {{--                            <a href="#" class="-m-2 p-2 text-white hover:text-white">--}}
                        {{--                                <span class="sr-only">Search</span>--}}
                        {{--                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">--}}
                        {{--                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"></path>--}}
                        {{--                                </svg>--}}
                        {{--                            </a>--}}
                    </div>

                    <span class="mx-4 h-6 w-px bg-gray-200" aria-hidden="true"></span>

                    <div class="flex">
                        <a href="{{ \Dashed\DashedCore\Classes\AccountHelper::getAccountUrl() }}"
                           class="-m-2 p-2 text-white hover:text-primary-500">
                            <span class="sr-only">Account</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                 stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"></path>
                            </svg>
                        </a>
                    </div>

                    <span class="mx-4 h-6 w-px bg-gray-200" aria-hidden="true"></span>

                    <div class="flow-root">
                        <a href="#"
                           @click="window.dispatchEvent(new CustomEvent('openCartPopup'))"
                           class="group -m-2 flex items-center p-2 trans">
                            <svg class="h-6 w-6 flex-shrink-0 text-white group-hover:text-primary-500 trans" fill="none"
                                 viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                      d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"></path>
                            </svg>
                            <span class="ml-2 text-sm font-bold text-white bg-primary-500 group-hover:bg-primary-800 rounded-full px-2 py-1 trans"><livewire:cart.cart-count/></span>
                            <span class="sr-only">items in cart, view bag</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-4 relative rounded-full bg-primary-500 p-2 lg:hidden">
                <x-icon-button
                        x-on:click="open = !open"
                        class="text-white hover:text-white size-6"
                        icon="lucide-menu"
                />
            </div>
        </nav>
    </x-container>
</header>
