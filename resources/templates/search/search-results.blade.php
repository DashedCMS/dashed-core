<section
    class="@if($blockData['top_margin'] ?? false) pt-16 sm:pt-24 @endif @if($blockData['bottom_margin'] ?? false) pb-16 sm:pb-24 @endif">
    <x-container :show="$blockData['in_container'] ?? true">
        <div class="grid gap-4">
            <h1 class="text-2xl font-bold md:text-4xl font-display">{{ $blockData['title'] }}</h1>

            <input wire:model.live="search" wire:change="searchForResults" wire:keyup="searchForResults" class="form-input max-w-[300px]"
                   placeholder="{{ Translation::get('search', 'search', 'Zoeken...') }}" type="text"/>

            @if($search)
                @if($searchResults['hasResults'])
                    <h2 class="text-lg md:text-2xl">{{ Translation::get('amount-of-results-found', 'search', ':count: resultaten gevonden', 'text', [
                    'count' => $searchResults['count']
                ]) }}</h2>
                    <div class="mt-8 space-y-4">
                        @foreach($searchResults['results'] as $searchResult)
                            @if($searchResult['hasResults'])
                                <div>
                                    <p>{{ Translation::get('results-for', 'search', 'Resultaten voor :name:', 'text', [
                                'name' => strtolower($searchResult['pluralName']),
                            ]) }}</p>
                                    <div class="space-y-2">
                                        @foreach($searchResult['results'] as $result)
                                            <a
                                                class="text-black flex gap-1 items-center group"
                                                href="{{ $result->getUrl() }}"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                     stroke-width="1.5" stroke="currentColor" class="size-6">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                          d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                                                </svg>

                                                <span class="group-hover:ml-2 trans">{{ $result->name }} {{ strtolower($searchResult['name']) }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <h2 class="text-lg md:text-2xl">{{ Translation::get('enter-search', 'search', 'Geen zoekresultaten voor :search:', 'text', [
                    'search' => $search
                ]) }}</h2>
                @endif
            @else
                <h2 class="text-lg md:text-2xl">{{ Translation::get('enter-search', 'search', 'Voer een zoekterm in') }}</h2>
            @endif
        </div>
    </x-container>
</section>
