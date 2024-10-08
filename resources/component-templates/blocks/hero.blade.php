<div class="pb-64 pt-32 relative overflow-hidden">
    <x-drift::image
            class="absolute inset-0 -z-10 h-full w-full object-cover"
            config="dashed"
            :path="$data['image']"
            :alt="$data['title']"
            :manipulations="[
                            'widen' => 1000,
                        ]"
    />
    <div class="absolute inset-0 -z-10 h-full w-full bg-black/20"></div>

    <x-container>
        <div class="max-w-3xl mx-auto text-center">
            <h1 class="text-4xl font-bold tracking-tight text-white lg:text-6xl">
                {{ $data['title'] }}
            </h1>

            @if($data['subtitle'] ?? false)
                <p class="mt-4 text-xl text-white font-bold">
                    {{ $data['subtitle'] }}
                </p>
            @endif
        </div>

        @if(count($data['buttons'] ?? []))
            <div class="grid items-center gap-4 md:flex mt-6 justify-center">
                @foreach ($data['buttons'] ?? [] as $button)
                    <x-button
                            type="button button--{{ $button['type'] }}"
                            href="{{ linkHelper()->getUrl($button) }}"
                    >{{ $button['title'] }}</x-button>
                @endforeach
            </div>
        @endif
    </x-container>
</div>
<div class="-mt-40 pb-16">
    <x-container>
        <div class="grid grid-cols-1 gap-y-6 md:grid-cols-2 md:grid-rows-2 md:gap-x-6 lg:gap-8">
            @foreach($data['categories'] ?? [] as $category)
            <div class="trans group aspect-h-1 aspect-w-2 overflow-hidden rounded-lg @if($loop->first) md:aspect-h-1 md:aspect-w-1 md:row-span-2 @else md:aspect-none md:relative md:h-full @endif">
                @if(!$loop->first)
                    <x-drift::image
                            class="object-cover object-center trans sm:absolute sm:inset-0 sm:h-full sm:w-full"
                            config="dashed"
                            :path="$category['image']"
                            :alt="$category['title']"
                            :manipulations="[
                            'widen' => 600,
                        ]"
                    />
                @else
                    <x-drift::image
                            class="object-cover object-center trans"
                            config="dashed"
                            :path="$category['image']"
                            :alt="$category['title']"
                            :manipulations="[
                            'widen' => 600,
                        ]"
                    />
                    @endif
                <div aria-hidden="true" class="bg-gradient-to-bl from-transparent to-black opacity-60 @if(!$loop->first) md:absolute md:inset-0 @endif"></div>
                <div class="flex items-end p-6 @if(!$loop->first) md:absolute md:inset-0 @endif">
                    <div>
                        <h2 class="font-semibold text-white text-xl sm:text-2xl">
                                {{ $category['title'] }}
                        </h2>

                        <p class="text-white max-w-[300px] text-sm sm:text-md">
                            {{ $category['subtitle'] }}
                        </p>

                        @if(count($category['buttons'] ?? []))
                            <div class="grid items-center gap-4 md:flex mt-4 justify-start">
                                @foreach ($category['buttons'] ?? [] as $button)
                                    <x-button
                                            type="button button--{{ $button['type'] }}"
                                            href="{{ linkHelper()->getUrl($button) }}"
                                    >{{ $button['title'] }}</x-button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endforeach
    </x-container>
</div>
