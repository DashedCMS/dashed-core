<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>IP-lijst van het CMS gewijzigd</title>
</head>
<body style="margin:0; padding:0; font-family: Arial, sans-serif; background-color:#f4f4f5; color:#111827;">
<table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:#f4f4f5; padding:24px 0;">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color:#ffffff; border-radius:8px; max-width:600px;">
                <tr>
                    <td style="padding:24px 32px; border-bottom:4px solid {{ $newEntries ? '#f59e0b' : '#f43f5e' }};">
                        <div style="font-size:14px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">
                            {{ $siteName ?? 'Dashed CMS' }}
                        </div>
                        <div style="font-size:22px; font-weight:bold; color:#111827;">
                            {{ $newEntries ? 'IP-lijst van het CMS gewijzigd' : 'IP-beperking van het CMS opgeheven' }}
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px 32px;">
                        <p style="font-size:16px; line-height:1.5; margin:0 0 16px 0;">
                            <strong>{{ $actor }}</strong> heeft op {{ $changedAt }}
                            @if ($actorIp) vanaf {{ $actorIp }} @endif
                            de lijst met IP-adressen gewijzigd waarvandaan het CMS bereikbaar is.
                        </p>

                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:14px; color:#374151; margin-top:8px;">
                            <tr>
                                <td width="50%" valign="top" style="padding:12px 16px; background-color:#f9fafb; border-radius:6px;">
                                    <div style="font-size:13px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Was</div>
                                    @forelse ($oldEntries as $entry)
                                        <div style="font-family: monospace; font-size:13px;">{{ $entry }}</div>
                                    @empty
                                        <div style="font-style:italic; color:#6b7280;">leeg, geen beperking</div>
                                    @endforelse
                                </td>
                                <td width="16"></td>
                                <td width="50%" valign="top" style="padding:12px 16px; background-color:#fffbeb; border-radius:6px;">
                                    <div style="font-size:13px; color:#6b7280; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:6px;">Is nu</div>
                                    @forelse ($newEntries as $entry)
                                        <div style="font-family: monospace; font-size:13px;">{{ $entry }}</div>
                                    @empty
                                        <div style="font-style:italic; color:#6b7280;">leeg, geen beperking</div>
                                    @endforelse
                                </td>
                            </tr>
                        </table>

                        <p style="font-size:14px; line-height:1.5; color:#6b7280; margin:24px 0 0 0;">
                            Herken je deze wijziging niet, kijk dan direct bij
                            <a href="{{ $settingsUrl }}" style="color:#2563eb;">Instellingen, Beveiliging</a>
                            en controleer wie er in het CMS kan.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
