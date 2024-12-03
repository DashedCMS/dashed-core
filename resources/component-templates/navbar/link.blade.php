@props(['menuItem', 'active' => true])

<li
        x-data="{ open: false }"
        x-on:mouseenter="open = true"
        x-on:mouseleave="open = false"
        x-bind:data-active="open"
        x-on:focusin="open = true"
        x-on:focusout="open = false"
        class="group px-3"
>
    <a
            x-bind:data-active="open"
            {{ $active ? 'data-active' : '' }}
            @class([
                'h-24 flex items-center justify-center relative font-bold uppercase',
                'data-[active]:text-primary-500',
                'data-[active]:after:opacity-100',
                'hover:after:opacity-100',
                'after:opacity-100' => $menuItem['active'],
                'after:content-content after:h-0.5 after:opacity-0 after:absolute after:bottom-0 after:inset-x-0 after:transition',
                'after:bg-gradient-to-r after:from-primary-500 after:to-primary-200',
            ])
            href="{{ $menuItem['url'] }}"
    >
        {{ $menuItem['name'] }}
        @if($menuItem['hasChilds'])
            <span>
                <x-lucide-chevron-down
                        class="w-4 h-4 text-white transition group-data-[active]:rotate-90 group-data-[active]:text-primary-500"
                />
        </span>
        @endif
    </a>

    @if ($slot ?? false)
        {{ $slot }}
    @endif
</li>
