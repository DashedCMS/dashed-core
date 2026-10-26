@php($calendar = $this->calendar())
<x-filament-panels::page>
    {{-- Eigen stijlblok: niet elk klantproject compileert de views uit vendor/dashed mee in Tailwind. --}}
    <style>
        .dsh-cal__bar { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem; margin-bottom: 1rem; }
        .dsh-cal__bar h2 { font-size: 1.125rem; font-weight: 600; margin-right: auto; text-transform: capitalize; }
        .dsh-cal__btn { padding: .35rem .75rem; border-radius: .5rem; border: 1px solid rgb(127 127 127 / .3); background: transparent; color: inherit; cursor: pointer; font-size: .875rem; }
        .dsh-cal__btn:hover { background: rgb(127 127 127 / .08); }
        .dsh-cal__select { padding: .35rem .5rem; border-radius: .5rem; border: 1px solid rgb(127 127 127 / .3); background: transparent; color: inherit; font-size: .875rem; }
        .dsh-cal__legend { display: flex; flex-wrap: wrap; gap: .75rem; font-size: .8rem; margin-bottom: .75rem; opacity: .85; }
        .dsh-cal__legend span::before { content: ''; display: inline-block; width: .6rem; height: .6rem; border-radius: 2px; margin-right: .3rem; background: var(--c); }
        .dsh-cal__conflicts { margin-bottom: 1rem; padding: .75rem 1rem; border-radius: .75rem; background: #fef3c7; color: #78350f; font-size: .875rem; }
        .dark .dsh-cal__conflicts { background: rgb(120 53 15 / .35); color: #fde68a; }
        .dsh-cal__conflicts ul { margin: .25rem 0 0; padding-left: 1.1rem; list-style: disc; }
        .dsh-cal__grid { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); border: 1px solid rgb(127 127 127 / .2); border-radius: .75rem; overflow: hidden; }
        .dsh-cal__head { padding: .4rem .5rem; font-size: .75rem; font-weight: 600; text-transform: uppercase; opacity: .6; border-bottom: 1px solid rgb(127 127 127 / .2); }
        .dsh-cal__day { min-height: 6.5rem; padding: .35rem; border-right: 1px solid rgb(127 127 127 / .12); border-bottom: 1px solid rgb(127 127 127 / .12); font-size: .75rem; }
        .dsh-cal__day--out { opacity: .45; }
        .dsh-cal__day--today .dsh-cal__num { background: #2563eb; color: #fff; border-radius: 999px; padding: 0 .35rem; }
        .dsh-cal__num { font-weight: 600; display: inline-block; margin-bottom: .25rem; }
        .dsh-cal__event { display: block; margin-bottom: 2px; padding: 1px 4px; border-radius: 4px; background: var(--c); color: #fff; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-decoration: none; }
        .dsh-cal__event--conflict { outline: 2px solid #f59e0b; }
        @media (max-width: 640px) { .dsh-cal__day { min-height: 4rem; } .dsh-cal__event { font-size: .65rem; } }
    </style>

    <div class="dsh-cal__bar">
        <h2>{{ $this->monthStart()->translatedFormat('F Y') }}</h2>
        @if (count($this->siteOptions()) > 1)
            <select class="dsh-cal__select" wire:model.live="site" aria-label="{{ __('Site') }}">
                @foreach ($this->siteOptions() as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        @endif
        <button type="button" class="dsh-cal__btn" wire:click="previousMonth">&larr; {{ __('Vorige') }}</button>
        <button type="button" class="dsh-cal__btn" wire:click="thisMonth">{{ __('Vandaag') }}</button>
        <button type="button" class="dsh-cal__btn" wire:click="nextMonth">{{ __('Volgende') }} &rarr;</button>
    </div>

    <div class="dsh-cal__legend">
        @foreach ($calendar['sources'] as $source)
            <span style="--c: {{ $source['color'] }}">{{ $source['label'] }}</span>
        @endforeach
    </div>

    @php($conflicting = collect($calendar['conflicts'])->flatMap(fn ($c) => [spl_object_id($c['a']), spl_object_id($c['b'])])->flip())
    @if ($calendar['conflicts'])
        <div class="dsh-cal__conflicts" data-calendar-conflicts>
            <strong>{{ __('Let op: dit loopt tegelijk') }}</strong>
            <ul>
                @foreach ($calendar['conflicts'] as $conflict)
                    <li>
                        {{ __(':a en :b, :van t/m :tot', [
                            'a' => $conflict['a']->title,
                            'b' => $conflict['b']->title,
                            'van' => $conflict['from']->translatedFormat('j M'),
                            'tot' => $conflict['to']->translatedFormat('j M'),
                        ]) }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="dsh-cal__grid">
        @foreach ([__('ma'), __('di'), __('wo'), __('do'), __('vr'), __('za'), __('zo')] as $weekday)
            <div class="dsh-cal__head">{{ $weekday }}</div>
        @endforeach
        @foreach ($calendar['weeks'] as $week)
            @foreach ($week as $day)
                <div @class(['dsh-cal__day', 'dsh-cal__day--out' => ! $day['inMonth'], 'dsh-cal__day--today' => $day['date']->isToday()])>
                    <span class="dsh-cal__num">{{ $day['date']->day }}</span>
                    @foreach ($day['events'] as $event)
                        @php($color = $calendar['sources'][$event->source]['color'] ?? '#6b7280')
                        <a @if ($event->url) href="{{ $event->url }}" @endif
                           @class(['dsh-cal__event', 'dsh-cal__event--conflict' => isset($conflicting[spl_object_id($event)])])
                           style="--c: {{ $color }}" title="{{ $event->title }}">{{ $event->title }}</a>
                    @endforeach
                </div>
            @endforeach
        @endforeach
    </div>
</x-filament-panels::page>
