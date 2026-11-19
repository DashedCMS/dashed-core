@php
    $result = $this->getResult();
    $fmt = fn ($value, $format) => \Dashed\DashedCore\Reports\ReportResult::format($value, $format);
@endphp
<x-filament-panels::page>
    <style>
        .drp-card { background: #fff; border: 1px solid rgba(0,0,0,.08); border-radius: 12px; padding: 16px; overflow-x: auto; }
        .dark .drp-card { background: rgb(24 24 27); border-color: rgba(255,255,255,.1); }
        .drp-meta { font-size: 13px; color: #6b7280; margin-bottom: 12px; }
        .drp-table { width: 100%; border-collapse: collapse; font-size: 14px; }
        .drp-table th, .drp-table td { padding: 8px 10px; border-bottom: 1px solid rgba(0,0,0,.06); }
        .dark .drp-table th, .dark .drp-table td { border-color: rgba(255,255,255,.08); }
        .drp-table th { text-align: left; font-weight: 600; }
        .drp-num { text-align: right !important; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .drp-total td { font-weight: 700; }
        .drp-chart { width: 100%; height: 260px; display: block; }
        .drp-bar { fill: var(--primary-500, #6366f1); }
        .drp-line { fill: none; stroke: var(--primary-500, #6366f1); stroke-width: 2; }
        .drp-axis { font-size: 10px; fill: #6b7280; }
        .drp-error { color: #b91c1c; }
    </style>

    @if(! $result)
        <div class="drp-card drp-error">{{ $this->error }}</div>
    @else
        <div class="drp-meta">
            {{ $result->from->format('d-m-Y') }} - {{ $result->to->format('d-m-Y') }}
            @if($result->truncated)
                &middot; {{ __('Alleen de eerste :aantal regels worden getoond.', ['aantal' => \Dashed\DashedCore\Reports\ReportRunner::MAX_ROWS]) }}
            @endif
        </div>

        @php
            $firstKey = array_key_first($result->columns);
            $points = collect($result->rows)->map(fn ($row) => ['label' => $row['group'], 'value' => (float) ($row[$firstKey] ?? 0)])->take(60)->values();
            $max = max(1, (float) $points->max('value'));
            $count = max(1, $points->count());
            $width = 1000; $height = 240; $pad = 20;
            $step = ($width - 2 * $pad) / $count;
        @endphp
        @if($this->record->chart !== 'none' && $points->count() > 0)
            <div class="drp-card">
                <div class="drp-meta">{{ $result->columns[$firstKey]['label'] }}</div>
                <svg class="drp-chart" viewBox="0 0 {{ $width }} {{ $height + 30 }}" preserveAspectRatio="none" role="img" aria-label="{{ $result->columns[$firstKey]['label'] }}">
                    @if($this->record->chart === 'line')
                        <polyline class="drp-line" points="{{ $points->map(fn ($p, $i) => round($pad + $step * $i + $step / 2, 1).','.round($height - ($p['value'] / $max) * ($height - 10), 1))->implode(' ') }}" />
                    @else
                        @foreach($points as $i => $point)
                            @php($h = max(0, ($point['value'] / $max) * ($height - 10)))
                            <rect class="drp-bar" x="{{ round($pad + $step * $i + $step * 0.1, 1) }}" y="{{ round($height - $h, 1) }}" width="{{ round($step * 0.8, 1) }}" height="{{ round($h, 1) }}"><title>{{ $point['label'] }}: {{ $fmt($point['value'], $result->columns[$firstKey]['format']) }}</title></rect>
                        @endforeach
                    @endif
                    @foreach($points as $i => $point)
                        @if($count <= 12 || $i % (int) ceil($count / 12) === 0)
                            <text class="drp-axis" x="{{ round($pad + $step * $i + $step / 2, 1) }}" y="{{ $height + 20 }}" text-anchor="middle">{{ \Illuminate\Support\Str::limit($point['label'], 14) }}</text>
                        @endif
                    @endforeach
                </svg>
            </div>
        @endif

        <div class="drp-card">
            <table class="drp-table">
                <thead>
                    <tr>
                        <th>{{ $result->groupLabel }}</th>
                        @foreach($result->columns as $column)
                            <th class="drp-num">{{ $column['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach($result->rows as $row)
                        <tr>
                            <td>{{ $row['group'] }}</td>
                            @foreach($result->columns as $key => $column)
                                <td class="drp-num">{{ $fmt($row[$key], $column['format']) }}</td>
                            @endforeach
                        </tr>
                    @endforeach
                    <tr class="drp-total">
                        <td>{{ __('Totaal') }}</td>
                        @foreach($result->columns as $key => $column)
                            <td class="drp-num">{{ $fmt($result->totals[$key], $column['format']) }}</td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    @endif
</x-filament-panels::page>
