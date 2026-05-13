@props([
    'definition',
    'health',
])

@php
    $status = $health->status;
    $bannerHeadings = [
        'connected' => 'Verbonden',
        'misconfigured' => 'Verkeerd geconfigureerd',
        'failing' => 'Probleem met de verbinding',
        'disabled' => 'Uitgeschakeld',
    ];
    $heading = $bannerHeadings[$status->value] ?? $status->label();
    $borderHex = $status->borderHex();
    $pillBg = $status->bgTintHex();
    $pillText = $status->pillTextHex();
    $dotHex = $status->dotHex();
@endphp

<div
    class="mb-4 flex items-start gap-3 rounded-xl bg-white p-4 ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 text-gray-950 dark:text-white"
    style="border-left: 4px solid {{ $borderHex }};"
>
    <span
        class="mt-1 inline-block size-2.5 shrink-0 rounded-full"
        style="background-color: {{ $dotHex }};"
    ></span>
    <div class="flex-1">
        <p class="text-sm font-semibold">
            Status:
            <span
                class="ms-1 inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                style="background-color: {{ $pillBg }}; color: {{ $pillText }}; border: 1px solid {{ $borderHex }};"
            >
                {{ $heading }}
            </span>
        </p>
        @if($health->message)
            <p class="mt-1 text-xs text-gray-600 dark:text-gray-300">{{ $health->message }}</p>
        @endif
        @if($health->lastSuccessAt)
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Laatst geslaagd {{ $health->lastSuccessAt->diffForHumans() }}</p>
        @endif
    </div>
</div>
