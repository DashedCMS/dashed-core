@php
    $entries = $getState() ?? [];
    $actions = \Dashed\DashedCore\PersonalData\PersonalDataFinding::actionLabels();
@endphp
<style>
    .dpdr-provider { margin-bottom: 1rem; }
    .dpdr-provider h4 { font-weight: 600; margin-bottom: .35rem; }
    .dpdr-table { width: 100%; border-collapse: collapse; font-size: .875rem; }
    .dpdr-table th, .dpdr-table td { text-align: left; padding: .35rem .5rem; border-bottom: 1px solid rgba(127,127,127,.2); vertical-align: top; }
    .dpdr-table th { font-weight: 500; opacity: .7; }
    .dpdr-badge { display: inline-block; padding: .05rem .45rem; border-radius: .375rem; font-size: .75rem; font-weight: 500; }
    .dpdr-delete { background: rgba(220,38,38,.12); color: rgb(185,28,28); }
    .dpdr-anonymize { background: rgba(217,119,6,.14); color: rgb(180,83,9); }
    .dpdr-unlink { background: rgba(37,99,235,.12); color: rgb(29,78,216); }
    .dpdr-keep { background: rgba(22,163,74,.12); color: rgb(21,128,61); }
    .dpdr-empty { opacity: .6; font-size: .875rem; }
    .dpdr-error { color: rgb(220,38,38); font-size: .875rem; }
</style>
@forelse ($entries as $entry)
    <div class="dpdr-provider">
        <h4>{{ $entry['label'] ?? $entry['provider'] }}</h4>
        @if (filled($entry['error'] ?? null))
            <p class="dpdr-error">{{ __('Fout') }}: {{ $entry['error'] }}</p>
        @endif
        @if (empty($entry['findings']))
            @if (blank($entry['error'] ?? null))
                <p class="dpdr-empty">{{ __('Niets gevonden.') }}</p>
            @endif
        @else
            <table class="dpdr-table">
                <thead>
                    <tr>
                        <th>{{ __('Bron') }}</th>
                        <th>{{ __('Aantal') }}</th>
                        <th>{{ __('Actie') }}</th>
                        <th>{{ __('Reden') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entry['findings'] as $finding)
                        <tr>
                            <td>{{ $finding['label'] }}</td>
                            <td>{{ $finding['count'] }}</td>
                            <td><span class="dpdr-badge dpdr-{{ $finding['action'] }}">{{ $actions[$finding['action']] ?? $finding['action'] }}</span></td>
                            <td>{{ $finding['reason'] ?? '' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@empty
    <p class="dpdr-empty">{{ __('Nog geen overzicht.') }}</p>
@endforelse
