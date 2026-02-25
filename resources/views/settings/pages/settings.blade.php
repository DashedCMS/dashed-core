<x-filament::page>

    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="text-gray-500 text-xs font-medium uppercase tracking-wide">Kies een optie</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Zoek snel je settings page 👀
                </p>
            </div>

            <div class="w-full sm:max-w-sm">
                <label for="settings-search" class="sr-only">Zoeken</label>
                <div class="relative">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                        <x-heroicon-o-magnifying-glass class="h-5 w-5" />
                    </div>

                    <input
                        id="settings-search"
                        type="text"
                        wire:model.live.debounce.200ms="search"
                        placeholder="Zoek op naam of omschrijving…"
                        class="block w-full rounded-lg border-gray-300 pl-10 pr-10 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500"
                    />

                    @if(strlen($search ?? '') > 0)
                        <button
                            type="button"
                            wire:click="$set('search','')"
                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600"
                        >
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                            <span class="sr-only">Wis</span>
                        </button>
                    @endif
                </div>
            </div>
        </div>

        @php
            $settingPages = $this->settingPages;
        @endphp

        @if($settingPages->isEmpty())
            <div class="rounded-lg border border-gray-200 bg-white p-6 text-sm text-gray-600">
                Geen resultaten voor: <span class="font-medium text-gray-900">{{ $search }}</span>
            </div>
        @else
            <ul class="grid grid-cols-1 gap-5 sm:gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($settingPages as $settingPage)
                    <li class="col-span-1 flex shadow-sm rounded-md">
                        <div class="shrink-0 flex items-center justify-center w-16 bg-primary-600 text-white text-sm font-medium rounded-l-md">
                            <a href="{{ $settingPage['page']::getUrl() }}">
                                <x-dynamic-component
                                    :component="'heroicon-o-' . $settingPage['icon']"
                                    class="w-6 h-6"
                                />
                            </a>
                        </div>

                        <div class="flex-1 flex items-center justify-between border-t border-r border-b border-gray-200 bg-white rounded-r-md">
                            <a href="{{ $settingPage['page']::getUrl() }}" class="flex-1">
                                <div class="px-4 py-2 text-sm">
                                    <h3 class="text-gray-900 font-medium hover:text-gray-600">
                                        {{ $settingPage['name'] }}
                                    </h3>
                                    <p class="text-gray-500">{{ $settingPage['description'] }}</p>
                                </div>
                            </a>

                            <div class="shrink-0 pr-4">
                                <a
                                    href="{{ $settingPage['page']::getUrl() }}"
                                    class="w-8 h-8 inline-flex items-center justify-center text-gray-400 rounded-full bg-transparent hover:text-gray-500 focus:outline-none ring-2 ring-offset-2 ring-primary-500 focus:ring-secondary-500"
                                >
                                    <span class="sr-only">Open optie</span>
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                         xmlns="http://www.w3.org/2000/svg" class="w-5 h-5">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M13 5l7 7-7 7M5 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

</x-filament::page>
