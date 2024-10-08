<header
        class="bg-white backdrop-blur-xl backdrop-saturate-200 sticky top-0 z-40 transform-gpu ring-1 ring-gray-900/5"
        x-data="{ open: false }"
>
    <div
            x-cloak
            x-show="open"
            x-transition.opacity.scale.origin.top
            class="absolute inset-x-0 bg-white top-16 ring-1 ring-gray-950/5 z-40 max-h-[calc(100dvh-5rem)] overflow-y-auto lg:hidden"
    >
        <x-container>
            <ul class="divide-y divide-gray-950/5 -mx-4">
                @foreach (Menus::getMenuItems('main-menu') as $menuItem)
                    <li>
                        <details class="group open:bg-gray-50">
                            <summary
                                    class="font-bold p-4 md:hover:bg-gray-100 transition text-sm marker:content-none flex items-center gap-2"
                            >
                                @if($menuItem['hasChilds'])
                                    <x-lucide-chevron-right
                                            class="w-4 h-4 text-black transition group-open:rotate-90 group-open:text-primary-500"
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
                                            <a class="p-4 transition text-sm block md:hover:bg-gray-100"
                                               href="{{ $child['url'] }}"
                                            >{{ $child['name'] }}</a>
                                        </li>
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
                        :mediaId="$logo"
                        :manipulations="[
                            'widen' => 300,
                        ]"
                />
            </a>

            <ul class="hidden lg:flex items-center text-black text-sm justify-center">
                @foreach (Menus::getMenuItems('main-menu') as $menuItem)
                    <x-navbar.link
                            :menuItem="$menuItem">
                        @if($menuItem['hasChilds'])
                            <x-navbar.mega-menu :menuItem="$menuItem"/>
                        @endif
                    </x-navbar.link>
                @endforeach
            </ul>

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
