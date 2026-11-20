{{--
    Aurora-prioriteitenlijst. Elk item: ['title', 'sub' => ?, 'url', 'badge'
    => ?, 'tone' => mint|blue|purple|amber|red|neutral]. Leeg: de tekst uit
    "empty".
--}}
@props(['items' => [], 'empty' => null])
@if (count($items) === 0)
    <p {{ $attributes->class(['au-pq-empty']) }}>
        <x-filament::icon icon="heroicon-o-check-circle" class="au-pq-empty-icon" />
        <span>{{ $empty ?? __('Niets dat nu aandacht vraagt.') }}</span>
    </p>
@else
    <ul {{ $attributes->class(['au-pq']) }}>
        @foreach ($items as $item)
            <li class="au-pq-row">
                <a href="{{ $item['url'] ?? '#' }}" class="au-pq-item au-tone-{{ $item['tone'] ?? 'neutral' }}">
                    <span class="au-pq-mark" aria-hidden="true"></span>
                    <span class="au-pq-text">
                        <span class="au-pq-title">{{ $item['title'] ?? '' }}</span>
                        @if (filled($item['sub'] ?? null))
                            <span class="au-pq-sub">{{ $item['sub'] }}</span>
                        @endif
                    </span>
                    @if (filled($item['badge'] ?? null))
                        <x-dashed-core::aurora.badge :tone="$item['tone'] ?? 'neutral'">{{ $item['badge'] }}</x-dashed-core::aurora.badge>
                    @endif
                    <x-filament::icon icon="heroicon-m-chevron-right" class="au-pq-chevron" />
                </a>
            </li>
        @endforeach
    </ul>
@endif
