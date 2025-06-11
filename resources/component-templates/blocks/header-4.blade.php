<section class="relative">
    @if($data['image'] ?? false)
        <x-dashed-files::image
            class="absolute inset-0 -z-10 h-full w-full object-cover"
            :mediaId="$data['image']"
            :alt="$data['title']"
            loading="eager"
            :manipulations="[
                            'widen' => 1000,
                        ]"
        />
        <div class="absolute inset-0 -z-10 h-full w-full bg-primary/20"></div>
    @endif
    <div
        class="w-full bg-primary/60 px-3 sm:px-10 @if($data['bottom_margin'] ?? true) pb-14 lg:pb-24 @endif @if($data['top_margin'] ?? true) lg:pt-40 pt-14 @endif grid gap-8 md:gap-16">
        <x-container :show="$data['in_container'] ?? false">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                <div class="flex items-center">
                    <div class="block max-lg:pt-6">
                        {{--                        <span class="text-sm font-normal text-primary-700 "># Revitalizingyourintrinsicbeauty</span>--}}
                        <h1 class="font-bold text-5xl leading-tight text-primary-900 my-8 pr-5 max-sm:break-all">
                            {!! nl2br($data['title']) !!}
                        </h1>
                        @if($data['subtitle'] ?? false)
                            <div
                                class="text-sm font-normal text-white prose">
                                {!! cms()->convertToHtml($data['subtitle']) !!}
                            </div>
                        @endif
                        @if(count($data['buttons'] ?? []))
                            <div class="grid items-center gap-4 md:flex mt-8">
                                @foreach ($data['buttons'] ?? [] as $button)
                                    <x-button
                                        type="button button--{{ $button['type'] }}"
                                        href="{{ linkHelper()->getUrl($button) }}"
                                        :button="$button"
                                    >{{ $button['title'] }}</x-button>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                <div class="pl-5">
                    <div class="grid grid-cols-2 gap-10">
                        <div class="flex flex-col gap-4 relative">
                            @if($data['image-2'] ?? false)
                                <x-dashed-files::image
                                    class="transition-all duration-500 cursor-pointer object-cover rounded-lg"
                                    :mediaId="$data['image-2']"
                                    loading="eager"
                                    :manipulations="[
                                        'widen' => 4000,
                                    ]"
                                />
                            @endif
                            @if($data['image-3'] ?? false)
                                <x-dashed-files::image
                                    class="transition-all duration-500 cursor-pointer object-cover rounded-lg"
                                    :mediaId="$data['image-3']"
                                    loading="eager"
                                    :manipulations="[
                                        'widen' => 4000,
                                    ]"
                                />
                            @endif
                            <svg class="absolute top-10 left-full w-10 h-12" width="39" height="43" viewBox="0 0 39 43"
                                 fill="none"
                                 xmlns="http://www.w3.org/2000/svg">
                                <path
                                    d="M10 0C10.3142 5.08513 12.581 7.2567 20 10C13.5542 11.384 11.2626 13.5577 10 20C8.51252 13.3218 6.27771 11.1232 0 10C6.43778 8.72424 8.59568 6.54823 10 0Z"
                                    fill="#047857"/>
                                <path
                                    d="M32 17C32.2199 20.5596 33.8067 22.0797 39 24C34.4879 24.9688 32.8838 26.4904 32 31C30.9588 26.3253 29.3944 24.7863 25 24C29.5064 23.107 31.017 21.5838 32 17Z"
                                    fill="#047857"/>
                                <path
                                    d="M21 33C21.1571 35.5426 22.2905 36.6284 26 38C22.7771 38.692 21.6313 39.7789 21 43C20.2563 39.6609 19.1389 38.5616 16 38C19.2189 37.3621 20.2978 36.2741 21 33Z"
                                    fill="#047857"/>
                            </svg>

                        </div>
                        @if($data['image-4'] ?? false)
                            <div class="flex h-full items-center">
                                <x-dashed-files::image
                                    class="object-cover rounded-lg"
                                    :mediaId="$data['image-4']"
                                    loading="eager"
                                    :manipulations="[
                                        'widen' => 4000,
                                    ]"
                                />
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </x-container>
        @if(count($data['usps'] ?? []))
            <x-container :show="$data['in_container'] ?? false">
                <div
                    class="grid grid-cols-1 min-[560px]:grid-cols-2 @if(count($data['usps'] ?? []) > 3) md:grid-cols-3 lg:grid-cols-4 @elseif(count($data['usps'] ?? []) == 3) md:grid-cols-3 @endif gap-8">
                    @foreach($data['usps'] ?? [] as $usp)
                        <a href="{{ linkHelper()->getUrl($usp) }}"
                           class="w-full min-[560px]:max-w-[280px] mx-auto py-4 flex items-center justify-center gap-3 text-sm font-medium text-primary-800 border border-gray-200 rounded-xl shadow-sm shadow-transparent transition-all duration-500 hover:border-primary-50 hover:bg-primary-50">
                            <span>
                                {!! $usp['icon'] !!}
                            </span>
                            <span>
                                {{ $usp['title'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </x-container>
        @endif
    </div>
</section>

