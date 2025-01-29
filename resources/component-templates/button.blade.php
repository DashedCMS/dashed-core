@props(['href', 'onclick', 'type', 'button' => []])

@php($tag = isset($href) ? 'a' : 'button')

<{{$tag}}
    @if($href ?? false)
    href="{{ $href }}"
@endif
@if($button['new_tab'] ?? false)
    target="_blank"
@endif
@if($onclick ?? false)
    onclick="{{ $onclick }}"
@endif
@class([
        $type,
])
>
<svg wire:loading wire:target="submit" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
     xmlns="http://www.w3.org/2000/svg" fill="none"
     viewBox="0 0 24 24">
    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
    <path class="opacity-75" fill="currentColor"
          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
</svg>
<span class="duration-300">{{ $slot }}</span>

{{--<svg--}}
{{--        xmlns="http://www.w3.org/2000/svg"--}}
{{--        fill="none"--}}
{{--        viewBox="0 0 24 24"--}}
{{--        stroke-width="1"--}}
{{--        stroke="currentColor"--}}
{{--        class="w-6 h-6 duration-500 group-hover:translate-x-4"--}}
{{-->--}}
{{--    <path--}}
{{--            stroke-linecap="round"--}}
{{--            stroke-linejoin="round"--}}
{{--            d="M17.25 8.25L21 12m0 0l-3.75 3.75M21 12H3"--}}
{{--    />--}}
{{--</svg>--}}
</{{$tag}}>
