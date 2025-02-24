<div class="relative isolate
overflow-hidden bg-gray-900 @if($data['top_margin'] ?? true) pt-24 sm:pt-36 @endif @if($data['bottom_margin'] ?? true) pb-24 sm:pb-36 @endif">
    <x-container :show="$data['in_container'] ?? true">
        <x-dashed-files::image
            class="absolute inset-0 -z-10 h-full w-full object-cover opacity-80"
            config="dashed"
            :mediaId="$data['image']"
            :alt="$data['title']"
            loading="eager"
            :manipulations="[
                            'widen' => 1000,
                        ]"
        />
        <div class="absolute inset-0 -z-10 h-full w-full bg-black/30"></div>

        <div class="max-w-4xl">
            <h1 class="text-3xl font-semibold tracking-tight text-white sm:text-5xl">
                {{ $data['title'] }}
            </h1>

            @if($data['subtitle'] ?? false)
                <div class="mt-6 text-lg leading-8 text-white font-semibold prose-base">
                    {!! $data['subtitle'] !!}
                </div>
            @endif

            @if(count($blockData['buttons'] ?? []))
                <div class="grid items-center gap-4 md:flex mt-4 justify-center">
                    @foreach ($blockData['buttons'] ?? [] as $button)
                        <x-button
                            type="button {{ $button['type'] }}"
                            href="{{ linkHelper()->getUrl($button) }}"
                            :button="$button"
                        >{{ $button['title'] }}</x-button>
                    @endforeach
                </div>
            @endif
        </div>
    </x-container>
</div>
