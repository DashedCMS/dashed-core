<div @class([
    'absolute bg-white/90 py-8 ring-1 ring-gray-950/5 z-40 w-[400px] -translate-x-1/2',
    'opacity-0 invisible',
    'group-data-[active]:visible group-data-[active]:opacity-100',
    'group-focus-within:visible group-focus-within:opacity-100',
])>
    <nav class="flex flex-1 flex-col" aria-label="Sidebar">
        <ul role="list" class="mx-2 space-y-1">
            @foreach ($menuItem['childs'] as $item)
                <li>
                    <!-- Current: "bg-gray-50 text-primary-600", Default: "text-gray-700 hover:text-primary-600 hover:bg-gray-50" -->
                    @if($item['active'])
                        <a href="{{ $item['url'] }}"
                           class="group flex gap-x-3 rounded-md bg-gray-50 p-2 pl-3 text-sm font-semibold leading-6 text-primary-600">{{ $item['name'] }}</a>
                    @else
                        <a href="{{ $item['url'] }}"
                           class="group flex gap-x-3 rounded-md p-2 pl-3 text-sm font-semibold leading-6 text-gray-700 hover:bg-gray-50 hover:text-primary-600">{{ $item['name'] }}</a>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>

</div>
