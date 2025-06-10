<div
    class="@if($data['top_margin'] ?? true) pt-32 @endif @if($data['bottom_margin'] ?? true) pb-64 @endif relative overflow-hidden">
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

    <x-container :show="$data['in_container'] ?? true">
        <div class="max-w-3xl mx-auto text-center">
            <h1 class="text-4xl font-bold tracking-tight text-white lg:text-6xl">
                {{ $data['title'] }}
            </h1>

            @if($data['subtitle'] ?? false)
                <div class="mt-4 text-xl text-white font-bold">
                    {!! cms()->convertToHtml($data['subtitle']) !!}
                </div>
            @endif
        </div>

        @if(count($data['buttons'] ?? []))
            <div class="grid items-center gap-4 md:flex mt-6 justify-center">
                @foreach ($data['buttons'] ?? [] as $button)
                    <x-button
                        type="button button--{{ $button['type'] }}"
                        href="{{ linkHelper()->getUrl($button) }}"
                        :button="$button"
                    >{{ $button['title'] }}</x-button>
                @endforeach
            </div>
        @endif
    </x-container>
</div>
