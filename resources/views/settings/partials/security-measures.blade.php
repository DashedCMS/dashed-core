@php
    $measures = \Dashed\DashedCore\Security\SecurityMeasures::all();
    $whereLabels = \Dashed\DashedCore\Security\SecurityMeasures::whereLabels();
    $whereColors = [
        \Dashed\DashedCore\Security\SecurityMeasures::HERE => 'primary',
        \Dashed\DashedCore\Security\SecurityMeasures::CMS => 'info',
        \Dashed\DashedCore\Security\SecurityMeasures::SERVER => 'warning',
        \Dashed\DashedCore\Security\SecurityMeasures::AUTOMATIC => 'success',
    ];
@endphp

<div class="divide-y divide-gray-200 dark:divide-white/10" data-security-measures>
    @foreach ($measures as $measure)
        <div class="py-3 flex flex-col gap-1 sm:flex-row sm:items-start sm:gap-4" data-measure="{{ \Illuminate\Support\Str::slug($measure['title']) }}">
            <div class="w-36 shrink-0 pt-0.5">
                <x-filament::badge :color="$whereColors[$measure['where']] ?? 'gray'">
                    {{ $whereLabels[$measure['where']] ?? $measure['where'] }}
                </x-filament::badge>
            </div>
            <div class="min-w-0">
                <div class="text-sm font-medium text-gray-950 dark:text-white">{{ $measure['title'] }}</div>
                <div class="text-sm text-gray-600 dark:text-gray-400">{{ $measure['body'] }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-500 mt-1">{{ $measure['location'] }}</div>
            </div>
        </div>
    @endforeach
</div>
