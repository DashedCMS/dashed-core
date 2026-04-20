<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $siteName ?? '' }}</title>
</head>
<body style="margin:0; padding:0; background:{{ $backgroundColor }};">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:{{ $backgroundColor }};">
        <tr><td align="center" style="padding:24px 12px;">
            @php
                $showSiteName = $showSiteName ?? true;
                $hasLogo = ! empty($siteLogo);
                $showHeader = $hasLogo || $showSiteName;
            @endphp
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; max-width:600px; overflow:hidden;">
                @if($showHeader)
                    <tr><td style="background:{{ $primaryColor }}; padding:20px 24px; text-align:center;">
                        @if($hasLogo)
                            <img src="{{ $siteLogo }}" alt="{{ $siteName }}" style="max-height:48px; display:inline-block;">
                        @elseif($showSiteName)
                            <span style="color:{{ $textColor }}; font-family: Arial, sans-serif; font-size:20px; font-weight:bold;">{{ $siteName }}</span>
                        @endif
                    </td></tr>
                @endif
                @foreach($blocks as $block)
                    {!! $block !!}
                @endforeach
                <tr><td align="center" style="padding:24px; color:#9ca3af; font-family: Arial, sans-serif; font-size:12px; border-top:1px solid #e5e7eb;">
                    {{ $footerText ?: ('© ' . date('Y') . ' ' . $siteName) }}
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
