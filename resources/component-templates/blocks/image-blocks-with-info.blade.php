<section
        class="border-t border-gray-200 bg-gray-50 @if($data['top_margin'] ?? false) pt-16 sm:pt-24 @endif @if($data['bottom_margin'] ?? false) pb-16 sm:pb-24 @endif">
    <x-container :show="$data['in_container'] ?? false">
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2">
            @foreach($data['blocks'] as $block)
                <div class="text-center relative bg-cover min-h-[350px]"
                     data-aos="fade-up"
                     style="background-image: url('{{ mediaHelper()->getSingleMedia($block['image'], [
    'widen' => 600,
])->url ?? '' }}')">
                    <div class="bg-black/50 p-8 h-full">
                        <div class="md:max-w-[80%] text-left mx-auto flex flex-col justify-between w-full h-full">
                            <div>
                                <h3 class="font-bold text-2xl md:text-3xl text-white">{{ $block['title'] }}</h3>
                                <p class="mt-3 text-gray-100 font-normal">{{ $block['subtitle'] }}</p>
                            </div>

                            @if(count($block['buttons'] ?? []))
                                <div class="grid items-center gap-4 md:flex justify-start">
                                    @foreach ($block['buttons'] ?? [] as $button)
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
                </div>
            @endforeach
        </div>
    </x-container>
</section>
