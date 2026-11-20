{{-- Aurora-regel: label links gedempt, waarde rechts. Zonder value telt de slot. --}}
@props(['label', 'value' => null])
<div {{ $attributes->class(['au-info-row']) }}>
    <span class="au-info-label">{{ $label }}</span>
    <span class="au-info-value">{{ $value ?? $slot }}</span>
</div>
