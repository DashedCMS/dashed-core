<x-filament::page>
    <div class="flex gap-6 min-h-[70vh]">

        {{-- Sidebar --}}
        <div class="w-72 shrink-0 space-y-3">
            {{-- Search --}}
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                    <x-heroicon-o-magnifying-glass class="h-4 w-4" />
                </div>
                <input
                    type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Zoek in documentatie..."
                    class="block w-full rounded-lg border-gray-300 pl-9 pr-9 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 py-2.5 bg-white dark:bg-gray-900 dark:border-gray-700 dark:text-white"
                />
                @if(strlen($search ?? '') > 0)
                    <button
                        type="button"
                        wire:click="$set('search','')"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                    >
                        <x-heroicon-o-x-mark class="h-4 w-4" />
                    </button>
                @endif
            </div>

            @if(strlen($search ?? '') >= 2)
                {{-- Search results --}}
                @php $results = $this->searchResults; @endphp
                <div class="space-y-1">
                    <p class="text-xs text-gray-500 px-2">{{ $results->count() }} resultaten</p>
                    @forelse($results as $result)
                        <button
                            type="button"
                            wire:click="selectArticle('{{ $result->package }}', '{{ $result->path }}')"
                            class="block w-full text-left rounded-lg px-3 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                        >
                            <span class="font-medium text-gray-900 dark:text-white">{{ $result->title }}</span>
                            <span class="block text-xs text-gray-500 mt-0.5">{{ $result->packageLabel }} &rsaquo; {{ $result->sectionLabel }}</span>
                        </button>
                    @empty
                        <p class="text-sm text-gray-500 px-2 py-4">Geen resultaten gevonden.</p>
                    @endforelse
                </div>
            @else
                {{-- Navigation tree --}}
                <nav class="space-y-1">
                    @foreach($this->navigationTree as $package => $section)
                        <div x-data="{ open: {{ $activePackage === $package ? 'true' : 'false' }} }">
                            <button
                                type="button"
                                @click="open = !open"
                                class="flex items-center justify-between w-full px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                            >
                                <span class="flex items-center gap-2">
                                    <x-dynamic-component :component="'heroicon-o-' . $section['icon']" class="w-4 h-4 text-gray-400" />
                                    {{ $section['label'] }}
                                </span>
                                <x-heroicon-o-chevron-down class="w-3.5 h-3.5 text-gray-400 transition-transform" x-bind:class="{ 'rotate-180': open }" />
                            </button>

                            <div x-show="open" x-collapse class="ml-3 mt-0.5 space-y-0.5 border-l border-gray-200 dark:border-gray-700">
                                @foreach($section['items'] as $item)
                                    @if($item['type'] === 'file')
                                        <button
                                            type="button"
                                            wire:click="selectArticle('{{ $package }}', '{{ $item['path'] }}')"
                                            @class([
                                                'block w-full text-left pl-4 pr-3 py-1.5 text-sm rounded-r-lg transition-colors',
                                                'bg-primary-50 text-primary-700 font-medium dark:bg-primary-900/20 dark:text-primary-400' => $activePackage === $package && $activePath === $item['path'],
                                                'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => $activePackage !== $package || $activePath !== $item['path'],
                                            ])
                                        >
                                            {{ $item['label'] }}
                                        </button>
                                    @elseif($item['type'] === 'folder')
                                        @php $folderActive = $activePackage === $package && collect($item['items'])->pluck('path')->contains($activePath); @endphp
                                        <div x-data="{ subOpen: {{ $folderActive ? 'true' : 'false' }} }">
                                            <button
                                                type="button"
                                                @click="subOpen = !subOpen"
                                                class="flex items-center justify-between w-full pl-4 pr-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-400 rounded-r-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors"
                                            >
                                                {{ $item['label'] }}
                                                <x-heroicon-o-chevron-down class="w-3 h-3 text-gray-400 transition-transform" x-bind:class="{ 'rotate-180': subOpen }" />
                                            </button>
                                            <div x-show="subOpen" x-collapse class="ml-3 space-y-0.5 border-l border-gray-200 dark:border-gray-700">
                                                @foreach($item['items'] as $subItem)
                                                    <button
                                                        type="button"
                                                        wire:click="selectArticle('{{ $package }}', '{{ $subItem['path'] }}')"
                                                        @class([
                                                            'block w-full text-left pl-4 pr-3 py-1.5 text-sm rounded-r-lg transition-colors',
                                                            'bg-primary-50 text-primary-700 font-medium dark:bg-primary-900/20 dark:text-primary-400' => $activePackage === $package && $activePath === $subItem['path'],
                                                            'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' => $activePackage !== $package || $activePath !== $subItem['path'],
                                                        ])
                                                    >
                                                        {{ $subItem['label'] }}
                                                    </button>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </nav>
            @endif
        </div>

        {{-- Main content --}}
        <div class="flex-1 min-w-0">
            @if($this->activeArticle)
                {{-- Breadcrumb --}}
                <div class="flex items-center gap-1.5 text-xs text-gray-500 mb-4">
                    <span>{{ $this->activeArticle->packageLabel }}</span>
                    @if($this->activeArticle->sectionLabel && $this->activeArticle->sectionLabel !== $this->activeArticle->packageLabel)
                        <x-heroicon-o-chevron-right class="w-3 h-3" />
                        <span>{{ $this->activeArticle->sectionLabel }}</span>
                    @endif
                    <x-heroicon-o-chevron-right class="w-3 h-3" />
                    <span class="text-gray-900 dark:text-white font-medium">{{ $this->activeArticle->title }}</span>
                </div>

                {{-- Article content --}}
                <div class="bg-white dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700 p-8">
                    <div class="prose prose-sm max-w-none dark:prose-invert prose-headings:font-semibold prose-h1:text-2xl prose-h2:text-lg prose-h2:border-b prose-h2:border-gray-200 prose-h2:pb-2 prose-h2:mt-8 prose-a:text-primary-600 prose-code:text-primary-600 prose-code:bg-gray-100 prose-code:px-1 prose-code:py-0.5 prose-code:rounded prose-code:text-sm prose-code:before:content-none prose-code:after:content-none dark:prose-code:bg-gray-800">
                        {!! $this->activeArticle->htmlContent !!}
                    </div>
                </div>

                {{-- Previous / Next --}}
                @php $adjacent = $this->adjacentArticles; @endphp
                @if($adjacent['previous'] || $adjacent['next'])
                    <div class="flex items-center justify-between mt-6 gap-4">
                        @if($adjacent['previous'])
                            <button
                                type="button"
                                wire:click="selectArticle('{{ $adjacent['previous']->package }}', '{{ $adjacent['previous']->path }}')"
                                class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors"
                            >
                                <x-heroicon-o-arrow-left class="w-4 h-4" />
                                {{ $adjacent['previous']->title }}
                            </button>
                        @else
                            <div></div>
                        @endif

                        @if($adjacent['next'])
                            <button
                                type="button"
                                wire:click="selectArticle('{{ $adjacent['next']->package }}', '{{ $adjacent['next']->path }}')"
                                class="flex items-center gap-2 text-sm text-gray-500 hover:text-gray-900 dark:hover:text-white transition-colors"
                            >
                                {{ $adjacent['next']->title }}
                                <x-heroicon-o-arrow-right class="w-4 h-4" />
                            </button>
                        @endif
                    </div>
                @endif
            @else
                <div class="flex items-center justify-center h-full text-gray-400">
                    <div class="text-center">
                        <x-heroicon-o-book-open class="w-12 h-12 mx-auto mb-3 opacity-50" />
                        <p class="text-sm">Selecteer een artikel uit het menu</p>
                    </div>
                </div>
            @endif
        </div>

    </div>
</x-filament::page>
