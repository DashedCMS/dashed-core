<section class="py-8 lg:py-16 bg-{{ $data['background_color'] }}">
    <x-dynamic-component :component="$container">
        <div class="flex flex-col items-center justify-center text-center">
            @if($data['toptitle'] ?? false)
            <p class="text-xs uppercase tracking-widest font-bold text-{{ $data['top_title_color'] }} font-mono">{{ $data['toptitle'] }}</p>
            @endif

            <h2 class="text-3xl md:text-4xl font-bold mt-8 text-{{ $data['title_color'] }}">
                {{ $data['title'] }}
            </h2>
        </div>

        <div class="mt-16 grid md:grid-cols-3 gap-8">
            @foreach($data['blocks'] ?? [] as $block)
            <a href="{{ linkHelper()->getUrl($block) }}"
               class="overflow-hidden rounded-md ring-1 ring-black/10 shadow-md shadow-black/10 bg-white transform trans hover:-translate-y-2">
                <div class="aspect-[21/9] flex items-center justify-center relative isolate text-white">
                    <x-dashed-files::image
                        class="h-full w-full object-cover"

                        :mediaId="$block['image']"
                        :alt="$block['title']"
                        :manipulations="[
                                    'widen' => 400,
                                ]"
                    />
                </div>
                <div class="p-8 relative">
                    <div
                        class="absolute -top-6 left-0 w-full flex items-center justify-center">
                        <div class="size-12 bg-ternair-light-blue rounded-full flex items-center justify-center text-ternair-dark-blue">
                            <x-dynamic-component
                                :component="'lucide-' . $block['icon']"
                                class="size-6"
                            />
                        </div>
                    </div>
                    @if($block['toptitle'] ?? false)
                    <p class="text-sm font-medium text-ternair-blue">{{ $block['toptitle'] }}</p>
                    @endif

                    <h3 class="font-semibold mt-2">{{ $block['title'] }}</h3>

                    @if($block['subtitle'] ?? false)
                    <p class="mt-2 text-ternair-dark-blue">
                        {{ $block['subtitle'] }}
                    </p>
                    @endif
                </div>
            </a>
            @endforeach
        </div>

        <article
            class="prose prose-headings:text-ternair-dark-blue prose-lg mx-auto prose-li:marker:text-ternair-blue prose-ul:list-[disclosure-closed] prose-blockquote:bg-ternair-light-blue prose-blockquote:py-1 prose-blockquote:text-ternair-dark-blue prose-blockquote:rounded-md prose-blockquote:border-ternair-blue"
        >
            {!! cms()->convertToHtml($data['content']) !!}
        </article>

        <div class="grid items-center justify-center gap-4 md:flex mt-16">
            @foreach ($data['buttons'] ?? [] as $button)
            <x-button :button="$button" type="button {{ $button['type'] }}"
                      href="{{ linkHelper()->getUrl($button) }}"
            >{{ $button['title'] }}</x-button>
            @endforeach
        </div>

        @if($data['image'] ?? false)
        <div class="flex items-center justify-center relative isolate text-white mt-8 rounded-md">
            <x-dashed-files::image
                class="h-full w-full object-cover rounded-md"

                :mediaId="$data['image']"
                :alt="$data['title']"
                :manipulations="[
                        'widen' => 1000,
                    ]"
            />
        </div>
        @endif
    </x-dynamic-component>
</section>
