<section
        class="@if($data['top_margin'] ?? false) pt-12 @endif @if($data['bottom_margin'] ?? false) pb-12 @endif relative isolate">
    <x-container :show="$data['in_container'] ?? true">
        <div class="absolute inset-4 overflow-hidden -z-10 rounded-3xl">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="currentColor"
                 class="size-48 top-0 right-0 text-primary absolute">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M9 5a2 2 0 0 1 2 2v6c0 3.13 -1.65 5.193 -4.757 5.97a1 1 0 1 1 -.486 -1.94c2.227 -.557 3.243 -1.827 3.243 -4.03v-1h-3a2 2 0 0 1 -1.995 -1.85l-.005 -.15v-3a2 2 0 0 1 2 -2z"/>
                <path d="M18 5a2 2 0 0 1 2 2v6c0 3.13 -1.65 5.193 -4.757 5.97a1 1 0 1 1 -.486 -1.94c2.227 -.557 3.243 -1.827 3.243 -4.03v-1h-3a2 2 0 0 1 -1.995 -1.85l-.005 -.15v-3a2 2 0 0 1 2 -2z"/>
            </svg>
            <div class="absolute left-1/2 inset-y-0 right-0 -skew-x-12 bg-primary/10 rounded-3xl -z-10"></div>
        </div>

        <div class="grid grid-cols-1 @if(!$data['hide_content']) lg:grid-cols-3 @endif gap-12 items-start">
            @if(!($data['hide_content']))
                <header class="lg:sticky top-48">
                    <h2 class="text-2xl md:text-3xl font-bold text-primary">{{ $data['title'] }}</h2>

                    @if($data['subtitle'] ?? false)
                        <p class="mt-2 font-handwritten text-xl font-medium text-secondary">
                            {{ $data['subtitle'] }}
                        </p>
                    @endif


                    @if(str(cms()->convertToHtml($data['content']))->stripTags())
                        <div class="mt-6 text-primary font-medium">
                            {!! cms()->convertToHtml($data['content'])!!}
                        </div>
                    @endif

                    @if(count($blockData['buttons'] ?? []))
                        <div class="grid items-center gap-4 md:flex mt-6 justify-center">
                            @foreach ($blockData['buttons'] ?? [] as $button)
                                <x-button
                                        type="button {{ $button['type'] }}"
                                        href="{{ linkHelper()->getUrl($button) }}"
                                        :button="$button"
                                >{{ $button['title'] }}</x-button>
                            @endforeach
                        </div>
                    @endif
                </header>
            @endif

            <div class="grid grid-cols-1 @if(!$data['hide_content']) md:grid-cols-2 lg:col-span-2 @else md:grid-cols-3 @endif gap-6">
                @foreach(\Dashed\DashedCore\Classes\Reviews::get(limit: $data['amount_of_reviews'], minStars: $data['min_stars'], random: $data['random_reviews']) as $review)
                    <div
                            class="p-6 rounded-2xl bg-white/75 backdrop-blur-3xl backdrop-saturate-150 shadow-xl shadow-black/5">
                        <div class="flex items-center text-primary">
                            @php($stars = $review->stars)
                            @while($stars > 0)
                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                     fill="currentColor" class="size-4">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M8.243 7.34l-6.38 .925l-.113 .023a1 1 0 0 0 -.44 1.684l4.622 4.499l-1.09 6.355l-.013 .11a1 1 0 0 0 1.464 .944l5.706 -3l5.693 3l.1 .046a1 1 0 0 0 1.352 -1.1l-1.091 -6.355l4.624 -4.5l.078 -.085a1 1 0 0 0 -.633 -1.62l-6.38 -.926l-2.852 -5.78a1 1 0 0 0 -1.794 0l-2.853 5.78z"/>
                                </svg>
                            @php($stars--)
                            @endwhile
                        </div>

                        <div class="text-primary font-medium mt-2">
                            {!! cms()->convertToHtml($review->review) !!}
                        </div>

                        <p class="text-lg mt-4 font-bold text-secondary relative">
                    <span
                            class="absolute -left-6 border-r-4 border-r-primary inset-y-0 rounded-r-full"></span>
                            <span>{{ $review->name }}</span>
                        </p>

                        <p class="text-primary">{{ \Carbon\Carbon::parse($review->created_at)->format('d.m.Y') }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </x-container>
</section>
