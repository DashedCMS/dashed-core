@props(['user' => null, 'size' => 'md'])
@php
    $px = ['sm' => 24, 'md' => 32, 'lg' => 48][$size] ?? 32;
    $name = $user?->name;
    $src = $user ? $user->avatar_url : \Dashed\DashedCore\Classes\Avatars\InitialsAvatar::url(null);
@endphp
<img
    src="{{ $src }}"
    alt="{{ $name ?? '' }}"
    @if ($name) title="{{ $name }}" @endif
    width="{{ $px }}"
    height="{{ $px }}"
    loading="lazy"
    decoding="async"
    {{ $attributes->merge(['class' => 'dashed-user-avatar inline-block shrink-0 rounded-full object-cover', 'style' => "width: {$px}px; height: {$px}px; border-radius: 9999px; object-fit: cover;"]) }}
/>
