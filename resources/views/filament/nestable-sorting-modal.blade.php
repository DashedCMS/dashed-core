@php
    /** @var array $tree */
    /** @var string $statePath */
    /** @var string $emptyLabel */
    $treeJson = json_encode($tree, JSON_THROW_ON_ERROR);
@endphp
<div
    x-data="dashedNestableSorting({ initial: {{ $treeJson }}, statePath: @js($statePath) })"
    x-init="init()"
    class="dashed-nestable"
    wire:ignore
>
    <p class="mb-3 text-sm text-gray-600 dark:text-gray-300">
        Sleep items om de volgorde te wijzigen. Sleep een item op een ander item om het als sub-item te plaatsen.
    </p>

    <ul
        class="dashed-nestable__root list-none p-0"
        data-parent-id=""
    >
        @foreach ($tree as $node)
            @include('dashed-core::filament._nestable-sorting-node', ['node' => $node])
        @endforeach

        @if (empty($tree))
            <li class="text-sm text-gray-500 italic px-3 py-2">
                {{ $emptyLabel }}
            </li>
        @endif
    </ul>
</div>
