@php
    $column = \Dashed\DashedCore\Classes\AbTests\AbTests::successColumn($test);
    $label = $column === 'clicks' ? __('Kliks') : __('Conversies');
    $pct = fn ($rate) => $rate === null ? '-' : number_format($rate * 100, 2, ',', '.') . '%';
@endphp
<div>
    <style>
        .dab-stats { width: 100%; border-collapse: collapse; font-size: .875rem; }
        .dab-stats th, .dab-stats td { text-align: left; padding: .4rem .5rem; border-bottom: 1px solid rgba(127,127,127,.2); }
        .dab-stats-note { margin-top: .75rem; font-size: .875rem; }
        .dab-stats-yes { color: #15803d; font-weight: 600; }
    </style>
    <table class="dab-stats">
        <thead>
        <tr>
            <th>{{ __('Variant') }}</th>
            <th>{{ __('Vertoningen') }}</th>
            <th>{{ __('Kliks') }}</th>
            <th>{{ __('Conversies') }}</th>
            <th>{{ __('Score (:soort per vertoning)', ['soort' => mb_strtolower($label)]) }}</th>
        </tr>
        </thead>
        <tbody>
        @foreach(['a', 'b'] as $variant)
            <tr>
                <td>{{ strtoupper($variant) }}</td>
                <td>{{ $stats[$variant]['views'] }}</td>
                <td>{{ $stats[$variant]['clicks'] }}</td>
                <td>{{ $stats[$variant]['conversions'] }}</td>
                <td>{{ $pct($stats[$variant]['rate']) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <p class="dab-stats-note">
        @if($stats['p'] === null)
            {{ __('Nog te weinig gegevens voor een uitspraak: minstens :aantal vertoningen per variant en minstens één :soort.', ['aantal' => \Dashed\DashedCore\Classes\AbTests\Significance::MIN_VIEWS, 'soort' => mb_strtolower($label)]) }}
        @elseif($stats['significant'])
            <span class="dab-stats-yes">{{ __('Variant :variant wint, met 95% zekerheid (p = :p).', ['variant' => strtoupper($stats['leader']), 'p' => number_format($stats['p'], 4, ',', '.')]) }}</span>
        @else
            {{ __('Nog geen duidelijke winnaar: het verschil kan toeval zijn (p = :p). Laat de test langer lopen.', ['p' => number_format($stats['p'], 4, ',', '.')]) }}
        @endif
    </p>
    <p class="dab-stats-note">{{ __('Elke browser telt één keer per test. Alleen bezoekers die toestemming gaven voor analytische cookies worden gemeten.') }}</p>
</div>
