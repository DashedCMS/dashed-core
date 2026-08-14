<tr><td style="padding:16px 24px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr><td style="border-left:4px solid {{ $primaryColor }};padding:4px 0 4px 16px;font-family:Georgia,serif;font-size:16px;font-style:italic;color:#3f3f46;">
            {!! nl2br(e($text)) !!}
            @if($source)
                <div style="margin-top:8px;font-family:Arial,Helvetica,sans-serif;font-size:13px;font-style:normal;color:#71717a;">{{ $source }}</div>
            @endif
        </td></tr>
    </table>
</td></tr>
