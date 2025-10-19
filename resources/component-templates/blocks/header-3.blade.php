<div>
    @if($data['image'] ?? false)
        <x-dashed-files::image
            class="absolute inset-0 -z-10 h-full w-full object-cover"
            config="dashed"
            :mediaId="$data['image']"
            :alt="$data['title']"
            loading="eager"
            :manipulations="[
                            'widen' => 1000,
                        ]"
        />
        <div class="absolute inset-0 -z-10 h-full w-full bg-black/20"></div>
    @endif
    <x-container :show="$data['in_container'] ?? false">
        <div
            class="w-full @if($data['bottom_margin'] ?? true) lg:pb-14 pb-10 @endif @if($data['top_margin'] ?? true) lg:pt-14 pt-10 @endif flex-col justify-start items-center lg:gap-16 gap-10 inline-flex">
            <div class="w-fit flex-col justify-start items-center gap-9 flex">
                <div class="flex-col justify-start items-center gap-3.5 flex">
                    <h1 class="text-center bg-clip-text text-transparent bg-linear-to-r from-primary-400 via-primary-900 to-primary-600 md:text-6xl text-5xl font-bold font-manrope md:leading-snug leading-snug">
                        {!! nl2br($data['title']) !!}
                    </h1>
                    @if($data['subtitle'] ?? false)
                        <div
                            class="lg:max-w-2xl w-full text-center text-gray-400 text-base font-normal leading-relaxed prose">
                            {!! cms()->convertToHtml($data['subtitle']) !!}
                        </div>
                    @endif
                </div>

                @if(count($data['buttons'] ?? []))
                    <div class="grid items-center gap-4 md:flex mt-6">
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
            @if(count($data['usps'] ?? []))
                <div
                    class="w-full justify-start items-start xl:gap-8 gap-5 grid lg:grid-cols-4 sm:grid-cols-2 grid-cols-1">
                    @foreach($data['usps'] ?? [] as $usp)
                        <div
                            class="group w-full h-full xl:p-7 lg:p-5 md:p-7 p-5 bg-primary-600 rounded-2xl border border-primary-700 hover:border-primary-400 transition-all duration-700 ease-in-out flex-col justify-start items-start inline-flex">
                            <div class="flex-col justify-start items-start gap-3 flex">
                                <div class="flex-col justify-start items-start gap-5 flex">
                                    <a href=""
                                       class="p-3 bg-white/5 group-hover:bg-white/10 transition-all duration-700 ease-in-out rounded-lg justify-center items-center gap-1 inline-flex">
                                        {!! $usp['icon'] !!}
                                    </a>
                                    <h4 class="text-white text-xl font-semibold leading-8">{{ $usp['title'] }} </h4>
                                </div>
                                <div class="text-white text-sm font-normal leading-snug">
                                    {!! cms()->convertToHtml($usp['subtitle']) !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </x-container>
</div>

