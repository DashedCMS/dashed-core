<div class="bg-white @if($data['top_margin'] ?? true) pt-16 sm:pt-24 @endif @if($data['bottom_margin'] ?? true) pb-16 sm:pb-24 @endif">
    <x-container :show="$data['in_container'] ?? false">
        <div>
            @if($data['title'] ?? false)
                <h2 class="text-3xl font-bold tracking-tight text-primary sm:text-4xl">{{ $data['title'] }}</h2>
            @endif
            @if($data['subtitle'] ?? false)
                <p class="text-base font-semibold leading-7 text-black">{{ $data['subtitle'] }}</p>
            @endif
            <dl class="@if(($data['title'] ?? false) || ($data['subtitle'] ?? false)) mt-10 @endif grid md:grid-cols-{{ $data['columns'] ?? 1 }} gap-4">
                @foreach($data['faqs'] ?? [] as $faq)
                    <details
                            class="open:bg-primary backdrop-blur-xl ring-1 rounded-xl ring-black/5 group open:ring-2 open:ring-primary open:shadow-xl open:shadow-primary/10 open:relative"
                    >
                        <summary
                                class="flex items-center gap-3 px-4 py-3 transition cursor-pointer marker:content-none"
                        >
                            <x-heroicon-s-chevron-right
                                    class="w-5 h-5 transition shrink-0 text-black/40 group-open:rotate-90 group-open:text-white"
                            />

                            <span class="font-bold transition group-open:text-white">{{ $faq['title'] }}</span>
                        </summary>

                        <div class="p-4 space-y-2 border-t border-black/5 bg-white content">
                            {!! nl2br(tiptap_converter()->asHTML($faq['content'])) !!}
                        </div>
                    </details>
                @endforeach
            </dl>
        </div>
    </x-container>
</div>
