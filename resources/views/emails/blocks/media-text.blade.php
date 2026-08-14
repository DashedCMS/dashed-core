<tr><td style="padding:16px 24px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            @php
                $afbeelding = '<td width="40%" valign="top" style="padding:0 8px;"><img src="' . e($image) . '" alt="" style="width:100%;display:block;border:0;"></td>';
                $tekst = '<td width="60%" valign="top" style="padding:0 8px;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#18181b;">' . nl2br(e($text));
                if ($buttonLabel && $buttonUrl) {
                    $tekst .= '<br><a href="' . e($buttonUrl) . '" style="display:inline-block;margin-top:12px;padding:8px 16px;background:' . e($primaryColor) . ';color:' . e($textColor) . ';text-decoration:none;border-radius:6px;font-size:13px;">' . e($buttonLabel) . '</a>';
                }
                $tekst .= '</td>';
            @endphp
            {!! $rechts ? $tekst . $afbeelding : $afbeelding . $tekst !!}
        </tr>
    </table>
</td></tr>
