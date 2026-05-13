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
@endphp

<div class="mb-4 flex items-start gap-3 rounded-xl p-4 {{ $status->pillClasses() }}">
    <span class="mt-1 inline-block size-2.5 shrink-0 rounded-full {{ $status->dotColor() }}"></span>
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
