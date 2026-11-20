{{--
    Aurora-voortgang. Elke stap: ['label', 'sub' => ?, 'state' =>
    done|current|todo|warning|danger]. Een 'date' (zoals OrderProgress levert)
    wordt de onderregel als er geen 'sub' is. De stand staat ook als tekst
    voor schermlezers en als icoon in het bolletje, niet alleen als kleur.
--}}
@props(['steps' => []])
@php
    $stateLabels = [
        'done' => __('Voltooid'),
        'current' => __('Huidige stap'),
        'todo' => __('Nog te doen'),
        'warning' => __('Let op'),
        'danger' => __('Probleem'),
    ];
@endphp
<ol {{ $attributes->class(['au-stepper']) }}>
    @foreach ($steps as $step)
        @php
            $state = array_key_exists($step['state'] ?? 'todo', $stateLabels) ? ($step['state'] ?? 'todo') : 'todo';
            $sub = $step['sub'] ?? ($step['date'] ?? null);
            if ($sub instanceof \DateTimeInterface) {
                $sub = \Illuminate\Support\Carbon::instance($sub)->format('d-m · H:i');
            }
        @endphp
        <li class="au-step au-step-{{ $state }}" @if ($state === 'current') aria-current="step" @endif>
            <span class="au-step-dot" aria-hidden="true">
                @switch($state)
                    @case('done')
                        <x-filament::icon icon="heroicon-m-check" class="au-step-icon" />
                        @break
                    @case('warning')
                        <x-filament::icon icon="heroicon-m-exclamation-triangle" class="au-step-icon" />
                        @break
                    @case('danger')
                        <x-filament::icon icon="heroicon-m-x-mark" class="au-step-icon" />
                        @break
                    @default
                        {{ $loop->iteration }}
                @endswitch
            </span>
            <strong class="au-step-label">{{ $step['label'] ?? '' }}</strong>
            <span class="au-sr-only">{{ $stateLabels[$state] }}</span>
            @if (filled($sub))
                <small class="au-step-sub">{{ $sub }}</small>
            @endif
        </li>
    @endforeach
</ol>
