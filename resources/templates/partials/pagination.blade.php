@php
    if (! isset($scrollTo)) {
        $scrollTo = 'body';
    }

    $scrollIntoViewJsSnippet = ($scrollTo !== false)
        ? <<<JS
           (\$el.closest('{$scrollTo}') || document.querySelector('{$scrollTo}')).scrollIntoView()
        JS
        : '';
@endphp

@if ($paginator->hasPages())
    <div class="flex items-center">
        {{-- Previous Page Link --}}
        @if ($paginator->onFirstPage())
            <span
                    class="rounded-l rounded-sm border border-primary px-3 py-2.5 cursor-not-allowed no-underline">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m18.75 4.5-7.5 7.5 7.5 7.5m-6-15L5.25 12l7.5 7.5" />
                </svg>
            </span>
            <span
                    class="border-r border-t border-b border-primary px-3 py-2.5 cursor-not-allowed no-underline">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </span>
        @else
            <span class="rounded-l rounded-sm border border-primary px-3 py-2.5 hover:bg-primary-800 hover:text-white hover:font-bold no-underline"
                  wire:click="gotoPage({{ 1 }})"
                  x-on:click="{{ $scrollIntoViewJsSnippet }}" rel="next">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m18.75 4.5-7.5 7.5 7.5 7.5m-6-15L5.25 12l7.5 7.5" />
                </svg>
            </span>
            <span
                    class="border-r border-t border-b border-primary px-3 py-2.5 hover:text-white hover:font-bold hover:bg-primary-800 no-underline"
                    wire:click="previousPage"
                    x-on:click="{{ $scrollIntoViewJsSnippet }}"
                    rel="prev"
            >
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>

            </span>
        @endif

        {{-- Pagination Elements --}}
        @foreach ($elements as $element)
            {{-- "Three Dots" Separator --}}
            @if (is_string($element))
                <span
                        class="border-t border-b border-l border-primary px-3 py-2 cursor-not-allowed no-underline">{{ $element }}</span>
            @endif

            {{-- Array Of Links --}}
            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span
                                class="border-t border-b border-l border-primary px-3 py-2 bg-primary-800 no-underline font-bold text-white">{{ $page }}</span>
                    @else
                        <span class="border-t border-b border-l border-primary px-3 py-2 hover:bg-primary-800 text-primary-light no-underline hover:text-white hover:font-bold"
                              wire:click="gotoPage({{ $page }})"
                              x-on:click="{{ $scrollIntoViewJsSnippet }}">{{ $page }}</span>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Next Page Link --}}
        @if ($paginator->hasMorePages())
            <span class="border-l border-t border-b border-primary px-3 py-2.5 hover:bg-primary-800 hover:text-white hover:font-bold no-underline"
                  wire:click="nextPage"
                  x-on:click="{{ $scrollIntoViewJsSnippet }}" rel="next">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </span>
            <span class="rounded-r rounded-sm border border-primary px-3 py-2.5 hover:bg-primary-800 hover:text-white hover:font-bold no-underline"
                  wire:click="gotoPage({{ $paginator->lastPage() }})"
                  x-on:click="{{ $scrollIntoViewJsSnippet }}" rel="next">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                </svg>
            </span>
        @else
            <span
                    class="border-l border-t border-b border-primary px-3 py-2.5 no-underline cursor-not-allowed">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </span>
            <span
                    class="rounded-r rounded-sm border border-primary px-3 py-2.5 no-underline cursor-not-allowed">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m5.25 4.5 7.5 7.5-7.5 7.5m6-15 7.5 7.5-7.5 7.5" />
                </svg>
            </span>
        @endif
    </div>
@endif
