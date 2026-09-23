<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif; background-color:#f4f4f5; color:#111827;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f5; padding:24px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff; border-radius:8px; max-width:600px;">
                <tr>
                    <td style="padding:24px 32px; border-bottom:4px solid #f59e0b;">
                        <div style="font-size:14px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">
                            {{ $siteName ?? 'Dashed CMS' }}
                        </div>
                        <div style="font-size:22px; font-weight:bold; color:#111827;">
                            {{ $title }}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px 32px;">
                        <p style="font-size:16px; line-height:1.5; margin:0 0 16px 0;">
                            @if (count($actions) > 1)
                                Er zijn beheeracties uitgevoerd die bewaakt worden. Herken je ze niet, controleer dan direct het account dat ze uitvoerde.
                            @else
                                Er is een beheeractie uitgevoerd die bewaakt wordt. Herken je deze actie niet, controleer dan direct het account dat hem uitvoerde.
                            @endif
                        </p>

                        @if ($context)
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:14px; color:#374151; background-color:#f9fafb; border-radius:6px; margin-bottom:16px;">
                                <tr>
                                    <td style="padding:12px 16px;">
                                        @foreach ($context as $label => $value)
                                            <div style="margin-top:4px;"><span style="color:#6b7280;">{{ $label }}:</span> {{ $value }}</div>
                                        @endforeach
                                    </td>
                                </tr>
                            </table>
                        @endif

                        @foreach ($actions as $action)
                            <table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:14px; color:#374151; background-color:#fffbeb; border-radius:6px; margin-bottom:12px;">
                                <tr>
                                    <td style="padding:12px 16px;">
                                        @if (count($actions) > 1)
                                            <div style="font-weight:bold; color:#111827; margin-bottom:4px;">{{ $action['title'] }}</div>
                                        @endif
                                        @foreach ($action['facts'] as $label => $value)
                                            <div style="margin-top:4px;"><span style="color:#6b7280;">{{ $label }}:</span> {{ $value }}</div>
                                        @endforeach
                                    </td>
                                </tr>
                            </table>
                        @endforeach
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
