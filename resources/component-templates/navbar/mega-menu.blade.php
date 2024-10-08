<div @class([
    'absolute inset-x-0 bg-white/90 top-16 py-8 ring-1 ring-gray-950/5 z-40',
    'opacity-0 invisible',
    'group-data-[active]:visible group-data-[active]:opacity-100',
    'group-focus-within:visible group-focus-within:opacity-100',
])>
    <x-container>
        @php
            $columns = count($menuItem['childs']);
            if($columns > 10){
                $columns = 10;
            }
            if($columns > 4){
            if ($columns % 2 == 0) {
                $columns = $columns / 2;
            } else {
                $columns = ($columns + 1) / 2;
            }
            }
        @endphp
        <div class="grid grid-cols-{{ $columns }} gap-4">
            @foreach ($menuItem['childs'] as $item)
                <div class="">
                    <a href="{{ $item['url'] }}"
                       class="text-gray-600 font-bold hover:text-primary-500 trans">{{ $item['name'] }}</a>

                    <div class="h-1 bg-gradient-to-r from-primary-500 to-primary-200 mt-2 rounded-lg"></div>

                    <ul class="space-y-2 mt-4">
                        @foreach ($item['childs'] as $child)
                            <li>
                                <a
                                        class="text-link"
                                        href="{{ $child['url'] }}"
                                >{{ $child['name'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </x-container>
</div>
