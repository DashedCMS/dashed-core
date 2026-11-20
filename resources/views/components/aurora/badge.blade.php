{{-- Aurora-badge. tone: mint (volgt de hoofdkleur) | blue | purple | amber | red | neutral. --}}
@props(['tone' => 'neutral'])
@php
    $tone = in_array($tone, ['mint', 'blue', 'purple', 'amber', 'red', 'neutral'], true) ? $tone : 'neutral';
@endphp
<span {{ $attributes->class(['au-badge', 'au-tone-'.$tone]) }}>{{ $slot }}</span>
