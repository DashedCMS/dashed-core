@php($calendar = $this->calendar())
<x-filament-panels::page>
    {{-- Eigen stijlblok: niet elk klantproject compileert de views uit vendor/dashed mee in Tailwind. --}}
    <style>
        .dsh-ccal__bar { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-bottom: 1rem; }
        .dsh-ccal__bar h2 { font-size: 1.125rem; font-weight: 600; margin-right: auto; text-transform: capitalize; }
        .dsh-ccal__btn { padding: .35rem .75rem; border-radius: .5rem; border: 1px solid rgb(127 127 127 / .3); background: transparent; color: inherit; cursor: pointer; font-size: .875rem; }
        .dsh-ccal__btn:hover { background: rgb(127 127 127 / .08); }
        .dsh-ccal__select { padding: .35rem .5rem; border-radius: .5rem; border: 1px solid rgb(127 127 127 / .3); background: transparent; color: inherit; font-size: .875rem; }
        .dsh-ccal__filters { display: flex; flex-wrap: wrap; gap: .5rem; margin-bottom: .75rem; }
        .dsh-ccal__filter { display: inline-flex; align-items: center; gap: .35rem; padding: .2rem .6rem; border-radius: 999px; border: 1px solid rgb(127 127 127 / .3); background: transparent; color: inherit; cursor: pointer; font-size: .8rem; }
        .dsh-ccal__filter::before { content: ''; width: .6rem; height: .6rem; border-radius: 2px; background: var(--c); }
        .dsh-ccal__filter[aria-pressed="false"] { opacity: .45; text-decoration: line-through; }
        .dsh-ccal__grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); border: 1px solid rgb(127 127 127 / .2); border-radius: .75rem; overflow: hidden; }
        .dsh-ccal__head { padding: .4rem .5rem; font-size: .75rem; font-weight: 600; text-transform: uppercase; opacity: .6; border-bottom: 1px solid rgb(127 127 127 / .2); }
        .dsh-ccal__day { min-height: 6.5rem; padding: .35rem; border-right: 1px solid rgb(127 127 127 / .12); border-bottom: 1px solid rgb(127 127 127 / .12); font-size: .75rem; min-width: 0; }
        .dsh-ccal__day--out { opacity: .45; }
        .dsh-ccal__day--today .dsh-ccal__num { background: #2563eb; color: #fff; border-radius: 999px; padding: 0 .35rem; }
        .dsh-ccal__num { font-weight: 600; display: inline-block; margin-bottom: .25rem; }
        .dsh-ccal__event { display: block; margin-bottom: 2px; padding: 1px 4px; border-radius: 4px; background: var(--c); color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-decoration: none; }
        @media (max-width: 640px) { .dsh-ccal__day { min-height: 4rem; } .dsh-ccal__event { font-size: .65rem; } }
    </style>

    <div class="dsh-ccal__bar">
        <h2>{{ $this->monthStart()->translatedFormat('F Y') }}</h2>
        @if (count($this->siteOptions()) > 1)
            <select class="dsh-ccal__select" wire:model.live="site" aria-label="{{ __('Site') }}">
                @foreach ($this->siteOptions() as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        @endif
        <button type="button" class="dsh-ccal__btn" wire:click="previousMonth">&larr; {{ __('Vorige') }}</button>
        <button type="button" class="dsh-ccal__btn" wire:click="thisMonth">{{ __('Vandaag') }}</button>
        <button type="button" class="dsh-ccal__btn" wire:click="nextMonth">{{ __('Volgende') }} &rarr;</button>
    </div>

    <div class="dsh-ccal__filters" role="group" aria-label="{{ __('Bronnen') }}">
        @foreach ($calendar['sources'] as $key => $source)
            <button type="button" class="dsh-ccal__filter" style="--c: {{ $source['color'] }}"
                    wire:click="toggleSource(@js($key))" aria-pressed="{{ $source['active'] ? 'true' : 'false' }}"
                    data-calendar-source="{{ $key }}">{{ $source['label'] }}</button>
        @endforeach
    </div>

    <div class="dsh-ccal__grid">
        @foreach ([__('ma'), __('di'), __('wo'), __('do'), __('vr'), __('za'), __('zo')] as $weekday)
            <div class="dsh-ccal__head">{{ $weekday }}</div>
        @endforeach
        @foreach ($calendar['weeks'] as $week)
            @foreach ($week as $day)
                <div @class(['dsh-ccal__day', 'dsh-ccal__day--out' => ! $day['inMonth'], 'dsh-ccal__day--today' => $day['date']->isToday()])>
                    <span class="dsh-ccal__num">{{ $day['date']->day }}</span>
                    @foreach ($day['events'] as $event)
                        <a @if ($event->url) href="{{ $event->url }}" @endif class="dsh-ccal__event"
                           style="--c: {{ $calendar['sources'][$event->source]['color'] ?? '#6b7280' }}" title="{{ $event->title }}">{{ $event->title }}</a>
                    @endforeach
                </div>
            @endforeach
        @endforeach
    </div>
</x-filament-panels::page>
