{{--
    Aurora-tijdlijn. Elk item: ['title', 'sub' => ?, 'time' => ?, 'tone' =>
    mint|blue|purple|amber|red|neutral]. time mag een datum zijn.
--}}
@props(['items' => [], 'empty' => null])
@if (count($items) === 0)
    <p {{ $attributes->class(['au-muted', 'au-timeline-empty']) }}>{{ $empty ?? __('Nog geen activiteit.') }}</p>
@else
    <ol {{ $attributes->class(['au-timeline']) }}>
        @foreach ($items as $item)
            @php
                $time = $item['time'] ?? null;
                if ($time instanceof \DateTimeInterface) {
                    $time = \Illuminate\Support\Carbon::instance($time)->format('d-m-Y H:i');
                }
            @endphp
            <li class="au-tl-item au-tone-{{ $item['tone'] ?? 'mint' }}">
                <span class="au-tl-pin" aria-hidden="true"></span>
                <span class="au-tl-text">
                    <span class="au-tl-title">{{ $item['title'] ?? '' }}</span>
                    @if (filled($item['sub'] ?? null))
                        <span class="au-tl-sub">{{ $item['sub'] }}</span>
                    @endif
                </span>
                @if (filled($time))
                    <time class="au-tl-time">{{ $time }}</time>
                @endif
            </li>
        @endforeach
    </ol>
@endif
