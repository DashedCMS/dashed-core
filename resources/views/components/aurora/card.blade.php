{{--
    Aurora-kaart: kop met icoon, titel, optionele badge en acties (slot
    "actions"), daaronder de inhoud. flush = geen binnenpadding, voor tabellen
    en lijsten die zelf tot de rand lopen.
--}}
@props([
    'title' => null,
    'icon' => null,
    'badge' => null,
    'badgeTone' => 'neutral',
    'flush' => false,
])
<section {{ $attributes->class(['au-card']) }}>
    @if (filled($title) || filled($badge) || isset($actions))
        <header class="au-card-head">
            <h3 class="au-card-title">
                @if ($icon)
                    <x-filament::icon :icon="$icon" class="au-card-icon" />
                @endif
                <span>{{ $title }}</span>
                @if (filled($badge))
                    <x-dashed-core::aurora.badge :tone="$badgeTone">{{ $badge }}</x-dashed-core::aurora.badge>
                @endif
            </h3>
            @isset($actions)
                <div class="au-card-actions">{{ $actions }}</div>
            @endisset
        </header>
    @endif
    <div @class(['au-card-body', 'au-card-body-flush' => $flush])>
        {{ $slot }}
    </div>
</section>
