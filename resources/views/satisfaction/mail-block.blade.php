{{-- Tevredenheidsvraag in een mail: vijf knoppen 1 tot 5, elk een ondertekende link. --}}
<tr><td align="center" style="padding:24px 24px 8px; font-family: Arial, Helvetica, sans-serif; border-top:1px solid #e5e7eb;">
    <div style="font-size:16px; font-weight:bold; color:#111827; margin-bottom:14px;">{{ $question }}</div>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center"><tr>
        @foreach($urls as $score => $url)
            <td style="padding:0 4px;">
                <a href="{{ $url }}" style="display:inline-block; width:40px; height:40px; line-height:40px; text-align:center; border-radius:20px; border:2px solid {{ $primaryColor }}; color:{{ $primaryColor }}; font-size:16px; font-weight:bold; text-decoration:none;">{{ $score }}</a>
            </td>
        @endforeach
    </tr></table>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" align="center" width="232" style="margin-top:6px;"><tr>
        <td align="left" style="font-size:11px; color:#9ca3af;">{{ $low }}</td>
        <td align="right" style="font-size:11px; color:#9ca3af;">{{ $high }}</td>
    </tr></table>
</td></tr>
