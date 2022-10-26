<x-filament::page>

    <div>
        <h2 class="text-gray-500 text-xs font-medium uppercase tracking-wide">Kies een optie</h2>
        <ul class="mt-3 grid grid-cols-1 gap-5 sm:gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach(cms()->builder('settingPages') as $settingPage)
                <li class="col-span-1 flex shadow-sm rounded-md">
                    <div
                        class="col-span-1 flex items-center justify-center w-16 bg-primary-600 text-white text-sm font-medium rounded-l-md">
                        <a class="col-span-1 flex"
                           href="{{ $settingPage['page']::getUrl() }}">
                            <x-dynamic-component :component="'heroicon-o-' . $settingPage['icon']"
                                                 class="w-6 h-6"></x-dynamic-component>
                        </a>
                    </div>
                    <div
                        class="flex-1 items-center justify-between border-t border-r border-b border-gray-200 bg-white rounded-r-md">
                        <a class="col-span-1 flex"
                           href="{{ $settingPage['page']::getUrl() }}">
                            <div class="flex-1 px-4 py-2 text-sm">
                                <h3
                                    class="text-gray-900 font-medium hover:text-gray-600">
                                    {{ $settingPage['name'] }}
                                </h3>
                                <p class="text-gray-500">{{ $settingPage['description'] }}</p>
                            </div>

                        </a>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>

</x-filament::page>
