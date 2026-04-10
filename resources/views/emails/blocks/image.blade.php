@if($src)
<tr><td align="center" style="padding: 8px 24px;">
    @if($url)
        <a href="{{ $url }}"><img src="{{ $src }}" alt="{{ $alt }}" style="max-width:100%; height:auto; display:block;"></a>
    @else
        <img src="{{ $src }}" alt="{{ $alt }}" style="max-width:100%; height:auto; display:block;">
    @endif
</td></tr>
@endif
