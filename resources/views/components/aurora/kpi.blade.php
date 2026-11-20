{{--
    Aurora-statistiektegel. tone: mint | blue | purple | amber. Met url wordt
    de hele tegel een link. delta-positive: true groen omhoog, false rood
    omlaag, null neutraal.
--}}
@props([
    'label',
    'value',
    'delta' => null,
    'deltaPositive' => null,
    'sub' => null,
    'tone' => 'mint',
    'icon' => null,
    'url' => null,
])
@php
    $tone = in_array($tone, ['mint', 'blue', 'purple', 'amber'], true) ? $tone : 'mint';
    $tag = filled($url) ? 'a' : 'div';
@endphp
<{{ $tag }} @if (filled($url)) href="{{ $url }}" @endif {{ $attributes->class(['au-kpi', 'au-tone-'.$tone]) }}>
    <span class="au-kpi-top">
        <span class="au-kpi-label">{{ $label }}</span>
        @if ($icon)
            <span class="au-kpi-icon" aria-hidden="true"><x-filament::icon :icon="$icon" /></span>
        @endif
    </span>
    <span class="au-kpi-value">{{ $value }}</span>
    @if (filled($delta) || filled($sub))
        <span class="au-kpi-foot">
            @if (filled($delta))
                <span @class([
                    'au-kpi-delta',
                    'au-kpi-delta-up' => $deltaPositive === true,
                    'au-kpi-delta-down' => $deltaPositive === false,
                ])>
                    @if ($deltaPositive === true)
                        <x-filament::icon icon="heroicon-m-arrow-trending-up" class="au-kpi-delta-icon" />
                    @elseif ($deltaPositive === false)
                        <x-filament::icon icon="heroicon-m-arrow-trending-down" class="au-kpi-delta-icon" />
                    @endif
                    {{ $delta }}
                </span>
            @endif
            @if (filled($sub))
                <span class="au-kpi-sub">{{ $sub }}</span>
            @endif
        </span>
    @endif
</{{ $tag }}>
