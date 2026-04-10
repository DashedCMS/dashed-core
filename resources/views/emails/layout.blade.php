<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $siteName ?? '' }}</title>
</head>
<body style="margin:0; padding:0; background:#f3f4f6;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;">
        <tr><td align="center" style="padding:24px 12px;">
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; max-width:600px;">
                @if(!empty($siteLogo))
                    <tr><td align="center" style="padding:24px 24px 0 24px;">
                        <img src="{{ $siteLogo }}" alt="{{ $siteName }}" style="max-height:48px;">
                    </td></tr>
                @endif
                @foreach($blocks as $block)
                    {!! $block !!}
                @endforeach
                <tr><td align="center" style="padding:24px; color:#9ca3af; font-family: Arial, sans-serif; font-size:12px;">
                    © {{ date('Y') }} {{ $siteName }}
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
