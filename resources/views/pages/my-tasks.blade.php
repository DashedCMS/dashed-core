@php($tiles = $this->getTaskTiles())
<x-filament-panels::page>
    @if ($tiles)
        {{-- Eigen stijlblok: niet elk klantproject compileert de views uit vendor/dashed mee in Tailwind. --}}
        <style>
            .dsh-tasks__groups { display: flex; flex-direction: column; gap: 1rem; }
            .dsh-tasks__group-title { font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; opacity: .6; margin-bottom: .4rem; }
            .dsh-tasks__grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(13rem, 1fr)); gap: .75rem; }
            .dsh-tasks__tile { display: flex; align-items: center; gap: .75rem; padding: .85rem 1rem; border: 1px solid rgb(127 127 127 / .2); border-radius: .75rem; background: rgb(255 255 255 / .02); color: inherit; text-decoration: none; transition: border-color .15s, background .15s; }
            a.dsh-tasks__tile:hover, a.dsh-tasks__tile:focus-visible { border-color: rgb(217 119 6 / .6); background: rgb(217 119 6 / .06); }
            .dsh-tasks__icon { flex: none; width: 1.5rem; height: 1.5rem; opacity: .7; }
            .dsh-tasks__label { flex: 1; min-width: 0; font-weight: 500; line-height: 1.25; }
            .dsh-tasks__count { flex: none; min-width: 1.75rem; padding: .1rem .5rem; border-radius: 999px; background: #d97706; color: #fff; font-weight: 700; font-size: .85rem; text-align: center; }
        </style>

        <div class="dsh-tasks__groups" data-task-tiles>
            @foreach (collect($tiles)->groupBy(fn ($tile) => $tile['group'] ?? '') as $group => $groupTiles)
                <section>
                    @if ($group !== '')
                        <h2 class="dsh-tasks__group-title">{{ __($group) }}</h2>
                    @endif
                    <div class="dsh-tasks__grid">
                        @foreach ($groupTiles as $tile)
                            @php($tag = $tile['url'] ? 'a' : 'div')
                            <{{ $tag }} @if ($tile['url']) href="{{ $tile['url'] }}" @endif class="dsh-tasks__tile" data-task-source="{{ $tile['key'] }}">
                                @if ($tile['icon'])
                                    <x-filament::icon :icon="$tile['icon']" class="dsh-tasks__icon" />
                                @endif
                                <span class="dsh-tasks__label">{{ $tile['label'] }}</span>
                                <span class="dsh-tasks__count">{{ $tile['count'] }}</span>
                            </{{ $tag }}>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
