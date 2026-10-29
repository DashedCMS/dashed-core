@php($items = \Dashed\DashedCore\Classes\Launch\LaunchChecklist::items())
@php($done = count(array_filter($items, fn ($item) => $item['done'])))
<x-filament-panels::page>
    {{-- Eigen stijlblok: niet elk klantproject compileert de views uit vendor/dashed mee in Tailwind. --}}
    <style>
        .dsh-launch__summary { font-size: 1.125rem; font-weight: 600; margin-bottom: .5rem; }
        .dsh-launch__bar { height: 10px; border-radius: 5px; background: rgb(127 127 127 / .18); overflow: hidden; margin-bottom: 1.25rem; }
        .dsh-launch__bar span { display: block; height: 100%; background: #16a34a; }
        .dsh-launch__list { border: 1px solid rgb(127 127 127 / .2); border-radius: .75rem; overflow: hidden; }
        .dsh-launch__item { display: flex; gap: .75rem; align-items: flex-start; padding: .75rem 1rem; }
        .dsh-launch__item + .dsh-launch__item { border-top: 1px solid rgb(127 127 127 / .15); }
        .dsh-launch__mark { flex: none; width: 1.5rem; height: 1.5rem; border-radius: 999px; display: flex; align-items: center; justify-content: center; font-size: .8rem; font-weight: 700; border: 2px solid rgb(127 127 127 / .35); background: transparent; color: inherit; }
        .dsh-launch__mark--done { background: #16a34a; border-color: #16a34a; color: #fff; }
        button.dsh-launch__mark { cursor: pointer; }
        .dsh-launch__body { flex: 1; min-width: 0; }
        .dsh-launch__label { font-weight: 500; }
        .dsh-launch__detail { font-size: .85rem; opacity: .7; margin-top: .15rem; overflow-wrap: anywhere; }
        .dsh-launch__tag { font-size: .7rem; padding: 0 .4rem; border-radius: 999px; border: 1px solid rgb(127 127 127 / .35); margin-left: .35rem; opacity: .75; }
        .dsh-launch__owner { font-size: .8rem; margin-top: .3rem; }
        .dsh-launch__owner strong { font-weight: 600; }
        .dsh-launch__problems { margin: .4rem 0 0; padding: .5rem .75rem .5rem 1.75rem; border-radius: .5rem; background: rgb(220 38 38 / .08); color: #b91c1c; font-size: .85rem; list-style: disc; }
        .dark .dsh-launch__problems { color: #fca5a5; background: rgb(220 38 38 / .15); }
        .dsh-launch__checked-by { font-size: .8rem; opacity: .7; margin-top: .3rem; }
        .dsh-launch__link { color: #2563eb; text-decoration: underline; font-size: .85rem; white-space: nowrap; }
    </style>

    <div class="dsh-launch__summary" data-launch-progress="{{ $done }}/{{ count($items) }}">
        {{ __('Je winkel is :klaar van :totaal klaar', ['klaar' => $done, 'totaal' => count($items)]) }}
    </div>
    <div class="dsh-launch__bar"><span style="width: {{ count($items) ? round($done / count($items) * 100) : 100 }}%"></span></div>

    <div class="dsh-launch__list">
        @foreach ($items as $item)
            <div class="dsh-launch__item" data-launch-item="{{ $item['key'] }}" data-done="{{ $item['done'] ? '1' : '0' }}">
                @if ($item['manual'])
                    <button type="button" wire:click="toggle('{{ $item['key'] }}')" @class(['dsh-launch__mark', 'dsh-launch__mark--done' => $item['done']]) aria-label="{{ __('Afvinken') }}">@if ($item['done'])&check;@endif</button>
                @else
                    <span @class(['dsh-launch__mark', 'dsh-launch__mark--done' => $item['done']])>@if ($item['done'])&check;@else&times;@endif</span>
                @endif
                <div class="dsh-launch__body">
                    <div class="dsh-launch__label">
                        {{ $item['label'] }}
                        @if ($item['manual'])<span class="dsh-launch__tag">{{ __('zelf afvinken') }}</span>@endif
                    </div>
                    @if ($item['detail'])
                        <div class="dsh-launch__detail">{{ $item['detail'] }}</div>
                    @endif
                    @if ($item['owner'])
                        <div class="dsh-launch__owner"><strong>{{ __('Wie regelt dit:') }}</strong> {{ $item['owner'] }}</div>
                    @endif
                    @if ($item['problems'])
                        <ul class="dsh-launch__problems" data-launch-problems="{{ $item['key'] }}">
                            @foreach ($item['problems'] as $problem)
                                <li>{{ $problem }}</li>
                            @endforeach
                        </ul>
                    @endif
                    @if ($item['checked_by'])
                        <div class="dsh-launch__checked-by">{{ __('Afgevinkt door :wie op :wanneer', ['wie' => $item['checked_by']['name'], 'wanneer' => \Illuminate\Support\Carbon::parse($item['checked_by']['at'])->format('d-m-Y H:i')]) }}</div>
                    @endif
                </div>
                @if ($item['url'])
                    <a class="dsh-launch__link" href="{{ $item['url'] }}">{{ __('Regelen') }} &rarr;</a>
                @endif
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
