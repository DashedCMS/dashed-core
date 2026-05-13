@php $state = $getState(); @endphp

@if($state)
    <div class="flex items-center gap-2" title="{{ $state['created_at']?->toDayDateTimeString() }}">
        @if($state['causer_avatar_url'])
            <img src="{{ $state['causer_avatar_url'] }}" class="size-6 rounded-full" alt="" />
        @else
            <span class="inline-flex size-6 items-center justify-center rounded-full bg-zinc-200 text-xs font-medium text-zinc-700 dark:bg-zinc-700 dark:text-zinc-200">
                {{ $state['causer_initials'] }}
            </span>
        @endif
        <span class="text-xs text-gray-700 dark:text-gray-300">
            {{ $state['causer_name'] }} <span class="text-gray-400">·</span> {{ $state['created_at_relative'] }}
        </span>
    </div>
@else
    <span class="text-xs text-zinc-400">-</span>
@endif
