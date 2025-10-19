<div class="relative isolate overflow-hidden bg-white px-6 py-16 lg:overflow-visible lg:px-0">
    <x-container>
        <div class="grid md:grid-cols-2 gap-8">
            @if(($data['image-left'] ?? false))
                <div class="flex"
                     data-aos="fade-left">
                    <x-dashed-files::image
                        class="w-full object-cover rounded-xl bg-gray-900 shadow-xl ring-1 ring-gray-400/10 sm:w-228"
                        config="dashed"
                        :mediaId="$data['image']"
                        :alt="$data['title']"
                        :manipulations="[
                            'widen' => 600,
                        ]"
                    />
                </div>
            @endif
            <div class="flex flex-col justify-center">
                <div class="lg:max-w-lg">
                    @if($data['subtitle'] ?? false)
                        <p class="text-base font-semibold leading-7 text-secondary">{{ $data['subtitle'] }}</p>
                    @endif
                    <h1 class="mt-2 text-3xl font-bold tracking-tight text-gray-900 sm:text-4xl">{{ $data['title'] }}</h1>
                </div>
                <div class="max-w-xl text-base leading-7 text-gray-700 lg:max-w-lg mt-4 prose">
                    {!! cms()->convertToHtml($data['content'] ?? '') !!}
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
            @if(!($data['image-left'] ?? false))
                <div class="flex"
                     data-aos="fade-left">
                    <x-dashed-files::image
                        class="w-full object-cover rounded-xl bg-gray-900 shadow-xl ring-1 ring-gray-400/10 sm:w-228"
                        config="dashed"
                        :mediaId="$data['image']"
                        :alt="$data['title']"
                        :manipulations="[
                            'widen' => 600,
                        ]"
                    />
                </div>
            @endif
        </div>
    </x-container>
</div>
