@php
    $src = $get('src');
    $w = $get('width');
    $h = $get('height');
@endphp

@if($src)
    <div class="mt-2">
        <img
            src="{{ $src }}"
            alt="{{ $get('alt') }}"
            title="{{ $get('title') }}"
            style="max-width:100%;height:auto;{{ $w ? 'width:'.$w.'px;' : '' }}{{ $h ? 'height:'.$h.'px;' : '' }}"
        >
    </div>
@endif
