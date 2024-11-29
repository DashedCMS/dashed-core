<div class="relative isolate
overflow-hidden bg-gray-900 @if($data['top_margin'] ?? true) pt-24 sm:pt-36 @endif @if($data['bottom_margin'] ?? true) pb-24 sm:pb-36 @endif">
    <x-container :show="$data['in_container'] ?? false">
        <x-dashed-files::image
                class="absolute inset-0 -z-10 h-full w-full object-cover opacity-80"
                config="dashed"
                :mediaId="$data['image']"
                :alt="$data['title']"
                :manipulations="[
                            'widen' => 1000,
                        ]"
        />
        <div class="mx-auto max-w-4xl text-center">
            <h1 class="text-3xl font-semibold tracking-tight text-primary-300 sm:text-5xl">
                {{ $data['title'] }}
            </h1>

            @if($data['subtitle'] ?? false)
                <p class="mt-6 text-lg leading-8 text-white font-semibold">
                    {{ $data['subtitle'] }}
                </p>
            @endif

            <div class="grid items-center gap-4 md:flex mt-6 justify-center">
                @foreach ($data['buttons'] ?? [] as $button)
                    <x-button
                            type="button {{ $button['type'] }}"
                            href="{{ linkHelper()->getUrl($button) }}"
                    >{{ $button['title'] }}</x-button>
                @endforeach
            </div>
        </div>
    </x-container>
</div>
