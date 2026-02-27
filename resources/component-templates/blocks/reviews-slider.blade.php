<section
        class="@if($data['top_margin'] ?? false) pt-12 @endif @if($data['bottom_margin'] ?? false) pb-12 @endif overflow-hidden">
    <x-container :show="$data['in_container'] ?? true">
        <header class="flex flex-col items-center justify-center gap-6">
            <h2 class="font-heading text-3xl text-center text-primary">{{ $data['title'] }}</h2>

            <div class="flex items-center justify-center gap-2">
                <div class="relative inline-flex items-center">
                    <div class="flex items-center gap-0.5">
                        @foreach (range(1, (int) round(\Dashed\DashedCore\Models\Review::avg('stars'))) as $i)
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"
                                 fill="currentColor" class="size-9 text-primary">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M8.243 7.34l-6.38 .925l-.113 .023a1 1 0 0 0 -.44 1.684l4.622 4.499l-1.09 6.355l-.013 .11a1 1 0 0 0 1.464 .944l5.706 -3l5.693 3l.1 .046a1 1 0 0 0 1.352 -1.1l-1.091 -6.355l4.624 -4.5l.078 -.085a1 1 0 0 0 -.633 -1.62l-6.38 -.926l-2.852 -5.78a1 1 0 0 0 -1.794 0l-2.853 5.78z"/>
                            </svg>
                        @endforeach
                    </div>
                </div>

                <p class="text-h6 font-heading text-blue">
                    {{ Translation::get('total-ratings', 'reviews', ':count: reviews', 'text', [
                        'count' => \Dashed\DashedCore\Models\Review::count(),
                    ]) }}
                </p>
            </div>
        </header>

        @php
            $reviews = \Dashed\DashedCore\Classes\Reviews::get(
                limit: $data['amount_of_reviews'],
                minStars: $data['min_stars'],
                random: $data['random_reviews']
            );
        @endphp

        <div
                class="relative mt-12"
                @mouseenter="stop()"
                @mouseleave="start()"
                x-data="{
    index: 0,
    perView: 1,
    interval: null,
    delay: 4000, // 4 seconden

    init() {
        this.setPerView();
        window.addEventListener('resize', () => this.setPerView());
        this.start();
    },

    setPerView() {
        const w = window.innerWidth;
        this.perView = w >= 1024 ? 3 : (w >= 768 ? 2 : 1);

        const max = this.maxIndex();
        if (this.index > max) this.index = max;
    },

    total() {
        return Number(this.$refs.track?.dataset?.total || 0);
    },

    maxIndex() {
        return Math.max(0, this.total() - this.perView);
    },

    next() {
        if (this.index >= this.maxIndex()) {
            this.index = 0; // 🔁 terug naar begin
        } else {
            this.index++;
        }
        this.reset();
    },

    prev() {
        if (this.index <= 0) {
            this.index = this.maxIndex();
        } else {
            this.index--;
        }
        this.reset();
    },

    goTo(i) {
        this.index = i;
        this.reset();
    },

    translatePct() {
        return -(this.index * (100 / this.perView));
    },

    start() {
        this.interval = setInterval(() => {
            this.next();
        }, this.delay);
    },

    stop() {
        clearInterval(this.interval);
    },

    reset() {
        this.stop();
        this.start();
    }
}"
        >
            {{-- Track viewport --}}
            <div class="overflow-hidden">
                {{-- Track --}}
                <div
                        x-ref="track"
                        data-total="{{ count($reviews) }}"
                        class="flex transition-transform duration-500 ease-out will-change-transform"
                        :style="`transform: translateX(${translatePct()}%);`"
                >
                    @foreach($reviews as $review)
                        <div class="shrink-0 w-full md:w-1/2 lg:w-1/3 px-2">
                            <div
                                    class="p-6 rounded-2xl bg-white/75 backdrop-blur-3xl backdrop-saturate-150 shadow-xl shadow-black/5 border-2 border-primary">
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
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Controls (rechtsboven, desktop) --}}
            <div class="hidden items-center absolute right-3 top-2 gap-4 md:flex">
                <button
                        type="button"
                        class="disabled:opacity-30 transition"
                        @click="prev()"
                        :disabled="!canPrev()"
                        aria-label="Previous"
                >
                    <svg class="size-8" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                </button>

                <button
                        type="button"
                        class="disabled:opacity-30 transition"
                        @click="next()"
                        :disabled="!canNext()"
                        aria-label="Next"
                >
                    <svg class="size-8 transform rotate-180" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                    </svg>
                </button>
            </div>

            {{-- Dots (optioneel, leuk op mobile) --}}
            <div class="mt-6 flex justify-center gap-2 md:hidden" x-show="total() > 1">
                <template x-for="i in (maxIndex() + 1)" :key="i">
                    <button
                            type="button"
                            class="h-2 w-2 rounded-full transition"
                            :class="(index === (i-1)) ? 'bg-primary' : 'bg-black/20'"
                            @click="goTo(i-1)"
                            aria-label="Go to slide"
                    ></button>
                </template>
            </div>
        </div>

        @if(count($data['buttons'] ?? []))
            <div class="grid items-center gap-4 md:flex mt-4 justify-center">
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
</section>