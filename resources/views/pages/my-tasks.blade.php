@php($tiles = $this->getTaskTiles())
<x-filament-panels::page>
    @if ($tiles)
        {{-- Eigen stijlblok: niet elk klantproject compileert de views uit vendor/dashed mee in Tailwind. --}}
        <style>
            .dsh-tasks__grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(12.5rem, 1fr)); gap: .75rem; }
            .dsh-tasks__tile { display: flex; align-items: center; gap: .75rem; width: 100%; padding: .75rem 1rem; border: 1px solid rgb(127 127 127 / .2); border-radius: .75rem; background: rgb(255 255 255 / .02); color: inherit; text-align: left; text-decoration: none; cursor: pointer; font: inherit; transition: border-color .15s, background .15s; }
            .dsh-tasks__tile:hover, .dsh-tasks__tile:focus-visible { border-color: rgb(217 119 6 / .6); background: rgb(217 119 6 / .06); }
            .dsh-tasks__tile[aria-pressed="true"] { border-color: #d97706; background: rgb(217 119 6 / .12); box-shadow: inset 0 0 0 1px #d97706; }
            .dsh-tasks__icon { flex: none; width: 1.5rem; height: 1.5rem; opacity: .7; }
            .dsh-tasks__text { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: .1rem; }
            .dsh-tasks__group { font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; opacity: .6; }
            .dsh-tasks__label { font-weight: 500; line-height: 1.25; }
            .dsh-tasks__count { flex: none; min-width: 1.75rem; padding: .1rem .5rem; border-radius: 999px; background: #d97706; color: #fff; font-weight: 700; font-size: .85rem; text-align: center; }
            .dsh-tasks__reset { margin-top: .5rem; font-size: .85rem; text-decoration: underline; opacity: .75; background: none; border: 0; padding: 0; color: inherit; cursor: pointer; }
        </style>

        <div>
            <div class="dsh-tasks__grid" data-task-tiles>
                @foreach ($tiles as $tile)
                    @php($active = $this->source === $tile['key'])
                    @if ($tile['has_items'])
                        <button type="button" wire:click="filterSource(@js($tile['key']))" aria-pressed="{{ $active ? 'true' : 'false' }}" class="dsh-tasks__tile" data-task-source="{{ $tile['key'] }}">
                    @else
                        <a @if ($tile['url']) href="{{ $tile['url'] }}" @endif class="dsh-tasks__tile" data-task-source="{{ $tile['key'] }}">
                    @endif
                        @if ($tile['icon'])
                            <x-filament::icon :icon="$tile['icon']" class="dsh-tasks__icon" />
                        @endif
                        <span class="dsh-tasks__text">
                            @if ($tile['group'])
                                <span class="dsh-tasks__group">{{ __($tile['group']) }}</span>
                            @endif
                            <span class="dsh-tasks__label">{{ $tile['label'] }}</span>
                        </span>
                        <span class="dsh-tasks__count">{{ $tile['count'] }}</span>
                    @if ($tile['has_items'])
                        </button>
                    @else
                        </a>
                    @endif
                @endforeach
            </div>

            @if ($this->source)
                <button type="button" wire:click="filterSource(null)" class="dsh-tasks__reset">{{ __('Alles tonen') }}</button>
            @endif
        </div>
    @endif

    {{ $this->table }}
</x-filament-panels::page>
