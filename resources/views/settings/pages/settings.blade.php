<x-filament::page>

    <div>
        <h2 class="text-gray-500 text-xs font-medium uppercase tracking-wide">Kies een optie</h2>
        <ul class="mt-3 grid grid-cols-1 gap-5 sm:gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach(cms()->builder('settingPages') as $settingPage)
                <li class="col-span-1 flex shadow-sm rounded-md">
                    <div
                        class="flex-shrink-0 flex items-center justify-center w-16 bg-primary-600 text-white text-sm font-medium rounded-l-md">
                        <x-dynamic-component :component="'heroicon-o-' . $settingPage['icon']"
                                             class="w-6 h-6"></x-dynamic-component>
                    </div>
                    <div
                        class="flex-1 flex items-center justify-between border-t border-r border-b border-gray-200 bg-white rounded-r-md">
                        <div class="flex-1 px-4 py-2 text-sm"><a
                                href="{{ $settingPage['page']::getUrl() }}"
                                class="text-gray-900 font-medium hover:text-gray-600">
                                {{ $settingPage['name'] }}
                            </a>
                            <p class="text-gray-500">{{ $settingPage['description'] }}</p>
                        </div>
                        <div class="flex-shrink-0 pr-2">
                            <a href="{{ $settingPage['page']::getUrl() }}"
                               class="w-8 h-8 bg-white inline-flex items-center justify-center text-gray-400 rounded-full bg-transparent hover:text-gray-500 focus:outline-none ring-2 ring-offset-2 ring-primary-500 focus:ring-secondary-500"><span
                                    class="sr-only">Open optie</span>
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
    </div>

</x-filament::page>
