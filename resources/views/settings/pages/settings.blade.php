<x-filament::page>
    {{--
        Eigen stijlblok in plaats van nieuwe Tailwind-klassen: niet elk
        klantproject compileert de views uit vendor/dashed mee, en dan zou een
        kaart zonder opmaak verschijnen. Kleuren komen uit Filaments eigen
        CSS-variabelen, zodat de huiskleur van het paneel meekomt.
    --}}
    <style>
        .ds-settings { display: flex; flex-direction: column; gap: 24px; }
        .ds-settings-head { display: flex; flex-direction: column; gap: 12px; }
        @media (min-width: 640px) { .ds-settings-head { flex-direction: row; align-items: flex-end; justify-content: space-between; } }
        .ds-settings-head p { margin: 0; font-size: 14px; color: rgb(113 113 122); }
        .ds-settings-search { position: relative; width: 100%; max-width: 380px; }
        .ds-settings-search svg { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); width: 18px; height: 18px; color: rgb(161 161 170); pointer-events: none; }
        .ds-settings-search input { width: 100%; padding: 10px 38px 10px 38px; font-size: 14px; border-radius: 10px; border: 1px solid rgb(228 228 231); background: #fff; color: rgb(24 24 27); box-shadow: 0 1px 2px rgb(0 0 0 / .04); }
        .ds-settings-search input:focus { outline: none; border-color: var(--primary-500); box-shadow: 0 0 0 3px color-mix(in oklab, var(--primary-500) 20%, transparent); }
        .ds-settings-search button { position: absolute; right: 8px; top: 50%; transform: translateY(-50%); padding: 4px; border: 0; background: none; color: rgb(161 161 170); cursor: pointer; }
        .ds-settings-search button svg { position: static; transform: none; }
        .ds-settings-grid { display: grid; grid-template-columns: 1fr; gap: 12px; margin: 0; padding: 0; list-style: none; }
        @media (min-width: 640px) { .ds-settings-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        @media (min-width: 1024px) { .ds-settings-grid { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        .ds-settings-card { display: flex; align-items: flex-start; gap: 14px; height: 100%; padding: 16px; border-radius: 12px; border: 1px solid rgb(228 228 231); background: #fff; color: inherit; text-decoration: none; transition: border-color .15s, box-shadow .15s, transform .15s; }
        .ds-settings-card:hover, .ds-settings-card:focus-visible { border-color: color-mix(in oklab, var(--primary-500) 45%, transparent); box-shadow: 0 6px 18px rgb(0 0 0 / .06); transform: translateY(-1px); outline: none; }
        .ds-settings-icon { flex: none; display: flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 10px; background: var(--primary-50); color: var(--primary-600); }
        .ds-settings-icon svg { width: 20px; height: 20px; }
        .ds-settings-text { flex: 1; min-width: 0; }
        .ds-settings-text h3 { margin: 0; font-size: 15px; font-weight: 600; color: rgb(24 24 27); }
        .ds-settings-text p { margin: 3px 0 0; font-size: 13px; line-height: 1.45; color: rgb(113 113 122); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .ds-settings-arrow { flex: none; align-self: center; width: 18px; height: 18px; color: rgb(161 161 170); transition: transform .15s, color .15s; }
        .ds-settings-card:hover .ds-settings-arrow { transform: translateX(3px); color: var(--primary-600); }
        .ds-settings-empty { padding: 20px; border-radius: 12px; border: 1px dashed rgb(212 212 216); font-size: 14px; color: rgb(82 82 91); }
        .dark .ds-settings-search input, .dark .ds-settings-card { background: rgb(24 24 27); border-color: rgb(255 255 255 / .1); color: rgb(244 244 245); }
        .dark .ds-settings-text h3 { color: rgb(244 244 245); }
        .dark .ds-settings-text p, .dark .ds-settings-head p { color: rgb(161 161 170); }
        .dark .ds-settings-icon { background: color-mix(in oklab, var(--primary-500) 15%, transparent); color: var(--primary-400); }
        .dark .ds-settings-empty { border-color: rgb(255 255 255 / .15); color: rgb(161 161 170); }
    </style>

    <div class="ds-settings">
        <div class="ds-settings-head">
            <p>{{ __('Kies een onderdeel om in te stellen, of zoek er direct naar.') }}</p>

            <div class="ds-settings-search">
                <label for="settings-search" class="sr-only">{{ __('Zoeken') }}</label>
                <x-heroicon-o-magnifying-glass />
                <input
                    id="settings-search"
                    type="text"
                    wire:model.live.debounce.200ms="search"
                    placeholder="{{ __('Zoek op naam of omschrijving...') }}"
                    autocomplete="off"
                />

                @if(strlen($search ?? '') > 0)
                    <button type="button" wire:click="$set('search','')">
                        <x-heroicon-o-x-mark class="h-5 w-5" />
                        <span class="sr-only">{{ __('Wis') }}</span>
                    </button>
                @endif
            </div>
        </div>

        @php
            $settingPages = $this->settingPages;
        @endphp

        @if($settingPages->isEmpty())
            <div class="ds-settings-empty">
                {{ __('Geen resultaten voor') }} <strong>{{ $search }}</strong>
            </div>
        @else
            <ul class="ds-settings-grid">
                @foreach($settingPages as $settingPage)
                    <li>
                        <a href="{{ $settingPage['page']::getUrl() }}" class="ds-settings-card">
                            <span class="ds-settings-icon">
                                <x-dynamic-component :component="'heroicon-o-' . $settingPage['icon']" />
                            </span>
                            <div class="ds-settings-text">
                                <h3>{{ $settingPage['name'] }}</h3>
                                <p>{{ $settingPage['description'] }}</p>
                            </div>
                            <x-heroicon-m-chevron-right class="ds-settings-arrow" />
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-filament::page>
