{{-- Een geneste tabel en geen flexbox: Outlook rendert met Word en kent geen
     moderne layout. Twee cellen van 50% is de vorm die overal werkt. --}}
<tr><td style="padding:16px 24px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            @foreach([$left, $right] as $kolom)
                <td width="50%" valign="top" style="padding:0 8px;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#18181b;">
                    @if($kolom['image'])
                        <img src="{{ $kolom['image'] }}" alt="" style="width:100%;max-width:260px;display:block;border:0;margin-bottom:8px;">
                    @endif
                    @if($kolom['text'])
                        <div>{!! nl2br(e($kolom['text'])) !!}</div>
                    @endif
                    @if($kolom['buttonLabel'] && $kolom['buttonUrl'])
                        <a href="{{ $kolom['buttonUrl'] }}" style="display:inline-block;margin-top:8px;padding:8px 16px;background:{{ $primaryColor }};color:{{ $textColor }};text-decoration:none;border-radius:6px;font-size:13px;">{{ $kolom['buttonLabel'] }}</a>
                    @endif
                </td>
            @endforeach
        </tr>
    </table>
</td></tr>
