<div @class([
    'absolute inset-x-0 bg-white/90 top-24 py-8 ring-1 ring-gray-950/5 z-40',
    'group-data-active:visible group-data-active:opacity-100',
    'group-focus-within:visible group-focus-within:opacity-100',
])
     x-show="open"
     x-cloak
     x-transition.opacity.scale.origin.top>
    @php($count = 0)
    <x-container>
        <div class="grid grid-cols-3 gap-4">
            @foreach ($menuItem['childs'] as $item)
                <div class="">
                    <a href="{{ $item['url'] }}"
                       class="text-gray-600 font-bold hover:text-primary-500 trans">{{ $item['name'] }}</a>

                    <div class="h-1 bg-linear-to-r from-primary-500 to-primary-200 mt-2 rounded-lg"></div>

                    <ul class="space-y-2 mt-4">
                        @foreach ($item['childs'] as $child)
                            <li>
                                <a
                                        class="text-link"
                                        href="{{ $child['url'] }}"
                                >{{ $child['name'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
                @php($count++)
            @endforeach
            @php($count += count($menuItem['contentBlocks']['submenu_images'] ?? []))
            @while($count < 3)
                <div></div>
                @php($count++)
            @endwhile
            @foreach($menuItem['contentBlocks']['submenu_images'] ?? [] as $image)
                <div class="relative group">
                    <a href="{{ linkHelper()->getUrl($image) }}">
                        <div class="px-8 py-4 bg-primary-600 text-white font-bold uppercase absolute bottom-[50px] left-0">
                            <p>{{ $image['title'] }}</p>
                        </div>
                        <x-dashed-files::image
                                class="w-full"
                                config="dashed"
                                :mediaId="$image['image']"
                                :alt="$image['title']"
                                :manipulations="[
                            'widen' => 300,
                        ]"
                        />
                    </a>
                </div>
            @endforeach
        </div>
    </x-container>
</div>
