{{--<div class="bg-linear-to-r from-primary-600 to-primary-300 px-4 md:px-8 py-4" wire:ignore>--}}
{{--    <div class="py-2 swiper swiper-usps">--}}
{{--        <ul--}}
{{--            class="gap-4 text-sm md:text-base text-white swiper-wrapper">--}}
{{--            <li class="swiper-slide">--}}
{{--                <div class="flex items-center justify-center gap-2 drop-shadow">--}}
{{--                    <x-lucide-gift class="size-6 text-primary-800"/>--}}

{{--                    <div class="">{!! Translation::get('usp-1', 'navbar', 'Gratis verzending vanaf &euro;99', 'editor') !!}</div>--}}
{{--                </div>--}}
{{--            </li>--}}

{{--            <li class="swiper-slide">--}}
{{--                <div class="flex items-center justify-center gap-2 drop-shadow">--}}
{{--                    <x-lucide-wallet class="size-6 text-primary-800"/>--}}

{{--                    <div class="">{!! Translation::get('usp-2', 'navbar', 'Veilig betalen', 'editor') !!}</div>--}}
{{--                </div>--}}
{{--            </li>--}}

{{--            <li class="swiper-slide">--}}
{{--                <div class="flex items-center justify-center gap-2 drop-shadow">--}}
{{--                    <x-lucide-heart-handshake class="size-6 text-primary-800"/>--}}

{{--                    <div class="">{!! Translation::get('usp-3', 'navbar', 'Persoonlijke service', 'editor') !!}</div>--}}
{{--                </div>--}}
{{--            </li>--}}

{{--            <li class="swiper-slide">--}}
{{--                <div class="flex items-center justify-center gap-2 drop-shadow">--}}
{{--                    <x-lucide-store class="size-6 text-primary-800"/>--}}

{{--                    <div class="">{!! Translation::get('usp-4', 'navbar', 'Alleen de beste kwaliteit', 'editor') !!}</div>--}}
{{--                </div>--}}
{{--            </li>--}}

{{--            <li class="swiper-slide">--}}
{{--                <div class="flex items-center justify-center gap-2 drop-shadow">--}}
{{--                    <x-lucide-undo-2 class="size-6 text-primary-800"/>--}}

{{--                    <div class="">{!! Translation::get('usp-5', 'navbar', 'Gratis retourneren', 'editor') !!}</div>--}}
{{--                </div>--}}
{{--            </li>--}}
{{--        </ul>--}}
{{--    </div>--}}
{{--</div>--}}

@php($headerInset = isset($model) && ($model->contentBlocks['header_inset'] ?? false) ? true : false)
<header
    class=" @if($headerInset) bg-primary-800/70 @else bg-primary-800 @endif text-white backdrop-blur backdrop-saturate-200 sticky top-0 z-40 transform-gpu ring-1 ring-primary-900/5 py-4"
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
                <x-dashed-files::image
                    class="h-12"
                    config="dashed"
                    :mediaId="Translation::get('light-logo', 'branding', null, 'image')"
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

            <div class="flex items-center gap-4 relative rounded-full bg-primary-500 p-2 lg:hidden">
                <x-button
                    x-on:click="open = !open"
                    class="text-white hover:text-white size-6"
                    icon="lucide-menu"
                />
            </div>
        </nav>
    </x-container>
</header>
