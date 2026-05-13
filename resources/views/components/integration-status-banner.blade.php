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
    $bgTint = $status->bgTintHex();
    $pillText = $status->pillTextHex();
    $borderHex = $status->borderHex();
    $dotHex = $status->dotHex();
@endphp

<div
    class="mb-4 flex items-start gap-3 rounded-xl p-4"
    style="background-color: {{ $bgTint }}; color: {{ $pillText }}; border-left: 4px solid {{ $borderHex }};"
>
    <span
        class="mt-1 inline-block size-2.5 shrink-0 rounded-full"
        style="background-color: {{ $dotHex }};"
    ></span>
    <div class="flex-1">
        <p class="text-sm font-semibold">
            Status: {{ $heading }}
        </p>
        @if($health->message)
            <p class="mt-0.5 text-xs">{{ $health->message }}</p>
        @endif
        @if($health->lastSuccessAt)
            <p class="mt-0.5 text-xs opacity-80">Laatst geslaagd {{ $health->lastSuccessAt->diffForHumans() }}</p>
        @endif
    </div>
</div>
