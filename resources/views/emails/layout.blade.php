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
                $siteUrl = $siteUrl ?? config('app.url');
                $hasSiteUrl = ! blank($siteUrl);
                $unsubscribeUrl = $unsubscribeUrl ?? null;
                $unsubscribeLabel = $unsubscribeLabel ?? 'Afmelden';
                $hasUnsubscribe = ! blank($unsubscribeUrl);
            @endphp
            <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:8px; max-width:600px; overflow:hidden;">
                @if($showHeader)
                    <tr><td style="background:{{ $primaryColor }}; padding:20px 24px; text-align:center;">
                        @if($hasLogo)
                            @if($hasSiteUrl)
                                <a href="{{ $siteUrl }}" style="display:inline-block; text-decoration:none;">
                                    <img src="{{ $siteLogo }}" alt="{{ $siteName }}" style="max-height:48px; display:inline-block; border:0;">
                                </a>
                            @else
                                <img src="{{ $siteLogo }}" alt="{{ $siteName }}" style="max-height:48px; display:inline-block; border:0;">
                            @endif
                        @elseif($showSiteName)
                            @if($hasSiteUrl)
                                <a href="{{ $siteUrl }}" style="color:{{ $textColor }}; font-family: Arial, sans-serif; font-size:20px; font-weight:bold; text-decoration:none;">{{ $siteName }}</a>
                            @else
                                <span style="color:{{ $textColor }}; font-family: Arial, sans-serif; font-size:20px; font-weight:bold;">{{ $siteName }}</span>
                            @endif
                        @endif
                    </td></tr>
                @endif
                @foreach($blocks as $block)
                    {!! $block !!}
                @endforeach
                @if($hasSiteUrl && ($showVisitSiteCta ?? false))
                    <tr><td align="center" style="padding: 0 24px 24px 24px;">
                        <a href="{{ $siteUrl }}" style="display:inline-block; padding:12px 24px; background:{{ $primaryColor }}; color:{{ $textColor }}; text-decoration:none; border-radius:6px; font-family: Arial, sans-serif; font-size:14px; font-weight:bold;">
                            Bezoek {{ $siteName }}
                        </a>
                    </td></tr>
                @endif
                <tr><td align="center" style="padding:24px; color:#9ca3af; font-family: Arial, sans-serif; font-size:12px; border-top:1px solid #e5e7eb;">
                    {{ $footerText ?: ('© ' . date('Y') . ' ' . $siteName) }}
                    @if($hasSiteUrl)
                        <br>
                        <a href="{{ $siteUrl }}" style="color:#9ca3af; text-decoration:underline;">{{ preg_replace('#^https?://#', '', rtrim($siteUrl, '/')) }}</a>
                    @endif
                    @if($hasUnsubscribe)
                        <br><br>
                        <a href="{{ $unsubscribeUrl }}" style="color:#9ca3af; text-decoration:underline; font-size:11px;">{{ $unsubscribeLabel }}</a>
                    @endif
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
