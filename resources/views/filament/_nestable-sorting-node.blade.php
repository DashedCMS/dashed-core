@php
    /** @var array $node */
@endphp
<li
    class="dashed-nestable__item rounded-lg bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 mb-1 shadow-sm"
    data-id="{{ $node['id'] }}"
>
    <div class="flex items-center gap-2 px-3 py-2">
        <span
            class="dashed-nestable__handle cursor-grab text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 select-none"
            aria-hidden="true"
        >&#x2630;</span>
        <span class="text-sm text-gray-900 dark:text-gray-100 flex-1 truncate">
            {{ $node['name'] ?: '#'.$node['id'] }}
        </span>
    </div>
    <ul
        class="dashed-nestable__children list-none pl-6 pr-2 pb-2"
        data-parent-id="{{ $node['id'] }}"
    >
        @foreach (($node['children'] ?? []) as $child)
            @include('dashed-core::filament._nestable-sorting-node', ['node' => $child])
        @endforeach
    </ul>
</li>
