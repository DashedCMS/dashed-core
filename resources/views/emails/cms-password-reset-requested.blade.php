<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <title>Wachtwoord-reset aangevraagd</title>
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
                            Wachtwoord-reset aangevraagd voor een beheerdersaccount
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding:24px 32px;">
                        <p style="font-size:16px; line-height:1.5; margin:0 0 16px 0;">
                            Op {{ $at }} is via het inlogscherm van het CMS een wachtwoord-reset aangevraagd voor
                            <strong>{{ $email }}</strong>.
                            @if ($linkSent)
                                De resetlink is naar dat adres gestuurd.
                            @else
                                Er is <strong>geen</strong> resetlink gestuurd: reset per mail staat voor beheerders uit op deze installatie.
                            @endif
                        </p>

                        <table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size:14px; color:#374151; background-color:#fffbeb; border-radius:6px;">
                            <tr>
                                <td style="padding:12px 16px;">
                                    <div><span style="color:#6b7280;">IP-adres:</span> <span style="font-family: monospace;">{{ $ip }}</span></div>
                                    <div style="margin-top:4px;"><span style="color:#6b7280;">Browser:</span> {{ $userAgent ?: 'onbekend' }}</div>
                                </td>
                            </tr>
                        </table>

                        <p style="font-size:14px; line-height:1.5; color:#6b7280; margin:24px 0 0 0;">
                            Heeft deze beheerder dit niet zelf gedaan, dan probeert iemand het account over te nemen via de mailbox. Controleer de mailbox van dit account en de MFA-instellingen.
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
